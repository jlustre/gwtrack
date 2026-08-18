<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\ChecklistType;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Support\ProfileLocationQuery;
use App\Services\Notifications\ChecklistNotificationDispatcher;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ChecklistService
{
    public function __construct(
        private readonly ChecklistNotificationDispatcher $checklistNotifications,
        private readonly NewMemberOnboardingTaskService $onboardingTasks,
    ) {}

    public function typeId(string $code): ?int
    {
        $id = ChecklistType::query()
            ->where('code', $code)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function defaultStartDateForUser(User $user): CarbonInterface
    {
        return ($user->joined_at ?? now())->copy()->startOfDay();
    }

    /**
     * @return list<string>
     */
    public function memberFacingTypeCodes(): array
    {
        return ['onboarding', 'licensing', 'fap', 'cfm-training'];
    }

    public function typeStartRecord(User $user, string $typeCode): ?UserChecklistTypeStart
    {
        $typeId = $this->typeId($typeCode);

        if (! $typeId) {
            return null;
        }

        return UserChecklistTypeStart::query()
            ->where('user_id', $user->id)
            ->where('checklist_type_id', $typeId)
            ->first();
    }

    public function hasTypeStarted(User $user, string $typeCode): bool
    {
        return $this->typeStartRecord($user, $typeCode) !== null;
    }

    public function typeStartDate(User $user, string $typeCode): ?CarbonInterface
    {
        return $this->typeStartRecord($user, $typeCode)?->started_at?->copy()->startOfDay();
    }

    public function canStartChecklistTypesFor(User $actor, User $member): bool
    {
        if ((int) $actor->id === (int) $member->id) {
            return true;
        }

        if ($actor->hasAnyRole(['super-admin', 'admin', 'agency-owner'])) {
            return true;
        }

        return $actor->hasRole('certified-field-mentor')
            && (int) $member->mentor_id === (int) $actor->id;
    }

    public function canStartChecklistTypeNow(User $actor, User $member, string $typeCode): bool
    {
        if (! $this->canStartChecklistTypesFor($actor, $member)) {
            return false;
        }

        if ($this->hasTypeStarted($member, $typeCode)) {
            return false;
        }

        if ($this->prerequisiteMet($member, $typeCode)) {
            return true;
        }

        return $actor->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * @return array<string, mixed>
     */
    public function notStartedViewData(
        User $actor,
        User $member,
        string $typeCode,
        string $checklistTypeName,
    ): array {
        $unmetPrerequisites = $this->prerequisitesForType($typeCode)
            ->filter(fn (ChecklistType $type) => ! $this->isTypeFullyCompleted($member, $type->code));

        return [
            'checklistTypeName' => $checklistTypeName,
            'typeCode' => $typeCode,
            'member' => $member,
            'canStartNow' => $this->canStartChecklistTypeNow($actor, $member, $typeCode),
            'isSelfStart' => (int) $actor->id === (int) $member->id,
            'unmetPrerequisites' => $unmetPrerequisites->pluck('name')->all(),
        ];
    }

    public function indexRouteForType(string $typeCode): string
    {
        return match ($typeCode) {
            'onboarding' => 'onboarding.index',
            'licensing' => 'licensing.index',
            'fap' => 'apprenticeship.index',
            'cfm-training' => 'cfm-training.index',
            default => 'dashboard',
        };
    }

    /**
     * @return Collection<int, ChecklistType>
     */
    public function startableTypesForMember(User $member): Collection
    {
        $startedTypeIds = UserChecklistTypeStart::query()
            ->where('user_id', $member->id)
            ->pluck('checklist_type_id');

        return ChecklistType::query()
            ->where('is_active', true)
            ->whereIn('code', $this->memberFacingTypeCodes())
            ->when($startedTypeIds->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('id', $startedTypeIds))
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ChecklistType $type) => $this->prerequisiteMet($member, $type->code))
            ->values();
    }

    public function startChecklistType(
        User $member,
        string $typeCode,
        User $startedBy,
        ?string $startedAt = null,
        ?int $taskUserId = null,
    ): UserChecklistTypeStart {
        if (! $this->canStartChecklistTypesFor($startedBy, $member)) {
            throw ValidationException::withMessages([
                'type' => 'You are not allowed to start this checklist for this member.',
            ]);
        }

        if ($this->hasTypeStarted($member, $typeCode)) {
            throw ValidationException::withMessages([
                'type' => 'This checklist has already been started.',
            ]);
        }

        if (! $this->prerequisiteMet($member, $typeCode) && ! $startedBy->hasAnyRole(['super-admin', 'admin'])) {
            throw ValidationException::withMessages([
                'type' => 'Prerequisite checklist types must be completed first.',
            ]);
        }

        $typeId = $this->typeId($typeCode);
        abort_unless($typeId, 404);

        $start = UserChecklistTypeStart::query()->create([
            'user_id' => $member->id,
            'checklist_type_id' => $typeId,
            'started_at' => $startedAt ?? now()->toDateString(),
            'started_by' => $startedBy->id,
        ]);

        $type = ChecklistType::query()->find($typeId);

        if ($type) {
            $this->checklistNotifications->typeStarted($member, $type, $startedBy);
        }

        if ($typeCode === 'onboarding') {
            $this->onboardingTasks->completeIfOpen($member, $taskUserId);
        }

        return $start;
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     started: bool,
     *     started_at: string|null,
     *     started_by: string|null,
     *     can_start: bool,
     *     prerequisites_met: bool
     * }>
     */
    public function checklistTypeManagementPanel(User $member, ?User $actor = null): array
    {
        $actor ??= auth()->user();
        abort_unless($actor instanceof User, 403);

        $started = UserChecklistTypeStart::query()
            ->where('user_id', $member->id)
            ->with(['checklistType', 'starter'])
            ->get()
            ->keyBy(fn (UserChecklistTypeStart $record) => $record->checklistType?->code);

        return collect($this->memberFacingTypeCodes())
            ->map(function (string $code) use ($actor, $member, $started): array {
                $type = ChecklistType::query()->where('code', $code)->first();
                $record = $started->get($code);

                return [
                    'code' => $code,
                    'name' => $type?->name ?? str($code)->replace('-', ' ')->title()->toString(),
                    'started' => $record !== null,
                    'started_at' => $record?->started_at?->format('M j, Y'),
                    'started_by' => $record?->starter?->name,
                    'can_start' => $record === null
                        && (bool) ($type?->is_active)
                        && $this->canStartChecklistTypeNow($actor, $member, $code),
                    'prerequisites_met' => $this->prerequisiteMet($member, $code),
                ];
            })
            ->values()
            ->all();
    }

    public function expectedDueDate(?int $nthDay, CarbonInterface $startDate): ?CarbonInterface
    {
        if ($nthDay === null || $nthDay < 1) {
            return null;
        }

        return $startDate->copy()->addDays($nthDay - 1);
    }

    public function maxCompleteDaysForType(string $typeCode): ?int
    {
        $typeId = $this->typeId($typeCode);

        if (! $typeId) {
            return null;
        }

        $maxDays = ChecklistType::query()->whereKey($typeId)->value('max_complete_days');

        return $maxDays !== null ? (int) $maxDays : null;
    }

    public function typeCompletionDueDate(CarbonInterface $startDate, string $typeCode): ?CarbonInterface
    {
        return $this->expectedDueDate($this->maxCompleteDaysForType($typeCode), $startDate);
    }

    public function prerequisiteForType(string $typeCode): ?ChecklistType
    {
        return $this->prerequisitesForType($typeCode)->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ChecklistType>
     */
    public function prerequisitesForType(string $typeCode): \Illuminate\Support\Collection
    {
        $type = ChecklistType::query()
            ->where('code', $typeCode)
            ->with('prerequisites')
            ->first();

        return $type?->prerequisites ?? collect();
    }

    public function prerequisiteMet(User $user, string $typeCode): bool
    {
        $prerequisites = $this->prerequisitesForType($typeCode);

        if ($prerequisites->isEmpty()) {
            return true;
        }

        return $prerequisites->every(
            fn (ChecklistType $prerequisite) => $this->isTypeFullyCompleted($user, $prerequisite->code),
        );
    }

    public function isTypeFullyCompleted(User $user, string $typeCode): bool
    {
        $country = $user->profile?->country;
        $groupLabel = $typeCode === 'fap' ? 'Field Apprenticeship Program' : null;
        $steps = $this->activeSteps(
            $typeCode,
            $typeCode === 'onboarding' ? $country : null,
            $groupLabel,
        );

        if ($steps->isEmpty()) {
            return true;
        }

        $targetSteps = $steps->where('is_required', true);

        if ($targetSteps->isEmpty()) {
            $targetSteps = $steps;
        }

        $progress = $this->userProgressFor($user->id, $targetSteps->pluck('id'));

        return $targetSteps->every(
            fn (Checklist $step) => ($progress->get($step->id)?->status ?? 'not_started') === 'completed',
        );
    }

    /**
     * @param  Collection<int, Checklist>  $steps
     * @return Collection<int, Checklist>
     */
    public function enrichStepsWithSchedule(Collection $steps, ?CarbonInterface $startDate): Collection
    {
        if (! $startDate) {
            return $steps;
        }

        return $steps->map(function (Checklist $step) use ($startDate): Checklist {
            $step->expected_due_date = $this->expectedDueDate($step->nth_day, $startDate);

            return $step;
        });
    }

    /**
     * @return Collection<int, Checklist>
     */
    public function activeSteps(string $typeCode, ?string $country = null, ?string $groupLabel = null): Collection
    {
        $query = Checklist::query()
            ->forTypeCode($typeCode)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($typeCode === 'onboarding') {
            $query->applicableToCountry($country);
        }

        if ($groupLabel !== null) {
            $query->where('group_label', $groupLabel);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $checklistIds
     * @return Collection<int, ChecklistProgress>
     */
    public function userProgressFor(int $userId, Collection|array $checklistIds): Collection
    {
        $ids = collect($checklistIds);

        if ($ids->isEmpty()) {
            return collect();
        }

        return ChecklistProgress::query()
            ->where('user_id', $userId)
            ->whereNull('mentor_assignment_id')
            ->whereIn('checklist_id', $ids)
            ->get()
            ->keyBy('checklist_id');
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $checklistIds
     * @return Collection<int, ChecklistProgress>
     */
    public function assignmentProgressFor(int $assignmentId, Collection|array $checklistIds): Collection
    {
        $ids = collect($checklistIds);

        if ($ids->isEmpty()) {
            return collect();
        }

        return ChecklistProgress::query()
            ->where('mentor_assignment_id', $assignmentId)
            ->whereIn('checklist_id', $ids)
            ->get()
            ->keyBy('checklist_id');
    }

    public function updateUserProgress(User $user, int $checklistId, bool $completed): void
    {
        abort_unless($this->activeChecklistExists($checklistId), 404);

        $existing = ChecklistProgress::query()
            ->where('user_id', $user->id)
            ->where('checklist_id', $checklistId)
            ->whereNull('mentor_assignment_id')
            ->first();

        // Confirmed items are locked — members cannot reopen or resubmit them.
        abort_if($existing?->status === 'completed', 403);

        $checklist = Checklist::query()->with('type')->find($checklistId);
        $requiresConfirmation = (bool) ($checklist?->requires_confirmation ?? true);
        $autoApprove = $completed && ! $requiresConfirmation;

        ChecklistProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'checklist_id' => $checklistId,
                'mentor_assignment_id' => null,
            ],
            [
                'status' => $autoApprove
                    ? 'completed'
                    : ($completed ? 'pending_confirmation' : 'not_started'),
                'submitted_at' => $completed ? now() : null,
                'completed_at' => $autoApprove ? now() : null,
                'completed_by' => $autoApprove ? $user->id : null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_comments' => null,
            ],
        );

        if ($completed && $checklist) {
            if ($autoApprove) {
                $typeCode = $checklist->type?->code;

                if ($typeCode) {
                    app(\App\Services\CfmEffectiveness\CfmMilestoneReviewTriggerService::class)
                        ->maybeTriggerChecklistCompletion($user, $typeCode);
                }
            } else {
                $this->checklistNotifications->itemSubmitted($user, $checklist);
            }
        }
    }

    public function reviewUserProgress(User $reviewer, int $progressId, string $decision, ?string $comments = null): void
    {
        $progress = ChecklistProgress::query()
            ->with(['user', 'checklist'])
            ->whereKey($progressId)
            ->memberProgress()
            ->pendingConfirmation()
            ->whereHas('user', fn (Builder $query) => $query->whereNull('deleted_at'))
            ->whereHas('checklist', fn (Builder $query) => $query->active())
            ->firstOrFail();

        $record = (object) [
            'sponsor_id' => $progress->user->sponsor_id,
            'mentor_id' => $progress->user->mentor_id,
            'notified_parties' => $progress->checklist->notified_parties,
        ];

        abort_unless($this->userCanConfirm($reviewer, $record), 403);

        $confirmed = $decision === 'confirmed';

        $progress->update([
            'status' => $confirmed ? 'completed' : 'rejected',
            'completed_at' => $confirmed ? now() : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_comments' => $comments,
        ]);

        $this->checklistNotifications->itemReviewed($progress->fresh(['user', 'checklist.type']), $reviewer, $confirmed);

        if ($confirmed) {
            $progress->loadMissing('checklist.type');
            $typeCode = $progress->checklist?->type?->code;

            if ($typeCode) {
                app(\App\Services\CfmEffectiveness\CfmMilestoneReviewTriggerService::class)
                    ->maybeTriggerChecklistCompletion($progress->user, $typeCode);
            }
        }
    }

    public function confirmationItemsFor(User $user, string $typeCode): Collection
    {
        $typeId = $this->typeId($typeCode);

        if (! $typeId) {
            return collect();
        }

        return ChecklistProgress::query()
            ->join('users', 'users.id', '=', 'checklist_progress.user_id')
            ->join('checklists', 'checklists.id', '=', 'checklist_progress.checklist_id')
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->tap(fn ($query) => ProfileLocationQuery::joinCountry($query))
            ->memberProgress()
            ->pendingConfirmation()
            ->where('checklists.checklist_type_id', $typeId)
            ->whereNull('users.deleted_at')
            ->whereNull('checklists.deleted_at')
            ->where('checklists.is_active', true)
            ->where('users.id', '!=', $user->id)
            ->select(
                'checklist_progress.id',
                'checklist_progress.submitted_at',
                'users.id as member_id',
                'users.name as member_name',
                'users.email as member_email',
                'users.sponsor_id',
                'users.mentor_id',
                ProfileLocationQuery::memberCountrySelect(),
                'checklists.title',
                'checklists.description',
                'checklists.notified_parties'
            )
            ->orderBy('checklist_progress.submitted_at')
            ->get()
            ->filter(fn (object $item) => $this->userCanConfirm($user, $item))
            ->values();
    }

    public function userCanConfirm(User $user, object $item): bool
    {
        $notifiedParties = collect(explode(',', (string) $item->notified_parties))
            ->map(fn (string $party) => strtoupper(trim($party)))
            ->filter()
            ->values();

        if ($notifiedParties->contains('SP') && (int) $item->sponsor_id === $user->id) {
            return true;
        }

        if ($notifiedParties->contains('CFM') && (int) $item->mentor_id === $user->id) {
            return true;
        }

        $roleMap = [
            'AO' => 'agency-owner',
            'TL' => 'team-leader',
            'TR' => 'trainer',
        ];

        foreach ($roleMap as $party => $role) {
            if ($notifiedParties->contains($party) && $user->hasRole($role)) {
                return true;
            }
        }

        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function checklistPercent(array $checklistIds, int $userId): int
    {
        if ($checklistIds === []) {
            return 0;
        }

        $completed = (int) ChecklistProgress::query()
            ->where('user_id', $userId)
            ->memberProgress()
            ->whereIn('checklist_id', $checklistIds)
            ->completed()
            ->count();

        return (int) round(($completed / count($checklistIds)) * 100);
    }

    public function activeChecklistExists(int $checklistId): bool
    {
        return Checklist::query()
            ->whereKey($checklistId)
            ->active()
            ->exists();
    }

    public function onboardingChecklistApplicable(Checklist $checklist, ?string $country): bool
    {
        return Checklist::query()
            ->whereKey($checklist->id)
            ->forTypeCode('onboarding')
            ->applicableToCountry($country)
            ->active()
            ->exists();
    }

    public function ensureAssignmentAccess(MentorAssignment $assignment, User $actingUser): void
    {
        if ($assignment->mentor_id !== $actingUser->id && ! $actingUser->hasAnyRole(['super-admin', 'admin'])) {
            abort(403);
        }
    }

    /**
     * @return array{
     *     assignment: MentorAssignment,
     *     trainee: User,
     *     stats: array<string, int|float>,
     *     phases: Collection<int, array<string, mixed>>
     * }
     */
    public function mentoringChecklistForAssignment(MentorAssignment $assignment): array
    {
        $assignment->loadMissing(['mentor', 'apprentice.rank', 'apprentice.profile']);

        $items = $this->activeSteps('cfm-mentoring');

        $progressByItem = $this->assignmentProgressFor($assignment->id, $items->pluck('id'));
        $startDate = ($assignment->started_at ?? $assignment->created_at ?? now())->copy()->startOfDay();

        $phases = $items
            ->groupBy('phase_number')
            ->map(function (Collection $phaseItems, int $phaseNumber) use ($progressByItem, $startDate) {
                $first = $phaseItems->first();
                $completed = $phaseItems->filter(
                    fn (Checklist $item) => ($progressByItem->get($item->id)?->status ?? 'not_started') === 'completed'
                )->count();

                return [
                    'phase_number' => $phaseNumber,
                    'phase_title' => $first->phase_title,
                    'phase_target' => $first->phase_target,
                    'total' => $phaseItems->count(),
                    'completed' => $completed,
                    'percent' => $phaseItems->count() > 0
                        ? (int) round(($completed / $phaseItems->count()) * 100)
                        : 0,
                    'sections' => $phaseItems
                        ->groupBy('section_title')
                        ->map(function (Collection $sectionItems) use ($progressByItem, $startDate) {
                            return [
                                'title' => $sectionItems->first()->section_title,
                                'items' => $sectionItems->map(function (Checklist $item) use ($progressByItem, $startDate) {
                                    $progress = $progressByItem->get($item->id);
                                    $link = config('fna.checklist_item_links.'.$item->slug);

                                    return [
                                        'id' => $item->id,
                                        'title' => $item->title,
                                        'description' => $item->description,
                                        'slug' => $item->slug,
                                        'nth_day' => $item->nth_day,
                                        'expected_due_date' => $this->expectedDueDate($item->nth_day, $startDate),
                                        'is_required' => $item->is_required,
                                        'is_completed' => ($progress?->status ?? 'not_started') === 'completed',
                                        'completed_at' => $progress?->completed_at,
                                        'notes' => $progress?->notes,
                                        'action_url' => $item->action_url ?: ($link ? route($link['route']) : null),
                                        'action_label' => $item->action_label ?: ($link['label'] ?? null),
                                    ];
                                })->values(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        $total = $items->count();
        $completed = $items->filter(
            fn (Checklist $item) => ($progressByItem->get($item->id)?->status ?? 'not_started') === 'completed'
        )->count();

        return [
            'assignment' => $assignment,
            'trainee' => $assignment->apprentice,
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'remaining' => $total - $completed,
                'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            ],
            'phases' => $phases,
        ];
    }

    public function updateMentoringProgress(
        MentorAssignment $assignment,
        Checklist $item,
        User $actingUser,
        bool $completed,
        ?string $notes = null,
    ): ChecklistProgress {
        $this->ensureAssignmentAccess($assignment, $actingUser);

        if (! $item->is_active || $item->type?->code !== 'cfm-mentoring') {
            throw ValidationException::withMessages([
                'item' => 'This checklist item is no longer active.',
            ]);
        }

        return ChecklistProgress::query()->updateOrCreate(
            [
                'mentor_assignment_id' => $assignment->id,
                'checklist_id' => $item->id,
            ],
            [
                'user_id' => null,
                'status' => $completed ? 'completed' : 'not_started',
                'completed_at' => $completed ? now() : null,
                'completed_by' => $completed ? $actingUser->id : null,
                'notes' => $notes,
            ],
        );
    }

    /**
     * @param  Collection<int, int>  $assignmentIds
     * @return array<int, int>
     */
    public function mentoringProgressPercentsForAssignments(Collection $assignmentIds): array
    {
        if ($assignmentIds->isEmpty()) {
            return [];
        }

        $totalItems = Checklist::query()
            ->forTypeCode('cfm-mentoring')
            ->active()
            ->count();

        if ($totalItems === 0) {
            return $assignmentIds->mapWithKeys(fn (int $id) => [$id => 0])->all();
        }

        $completedCounts = ChecklistProgress::query()
            ->whereIn('mentor_assignment_id', $assignmentIds)
            ->where('status', 'completed')
            ->selectRaw('mentor_assignment_id, COUNT(*) as completed_count')
            ->groupBy('mentor_assignment_id')
            ->pluck('completed_count', 'mentor_assignment_id');

        return $assignmentIds->mapWithKeys(function (int $id) use ($completedCounts, $totalItems) {
            $completed = (int) ($completedCounts[$id] ?? 0);

            return [$id => (int) round(($completed / $totalItems) * 100)];
        })->all();
    }

    /**
     * @return Builder<Checklist>
     */
    public function activeStepsQuery(string $typeCode): Builder
    {
        return Checklist::query()
            ->forTypeCode($typeCode)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    /**
     * @return array<int, int>
     */
    public function activeChecklistIdsForType(string $typeCode): array
    {
        return Checklist::query()
            ->forTypeCode($typeCode)
            ->active()
            ->pluck('id')
            ->all();
    }
}
