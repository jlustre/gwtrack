<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\CfmAdvancementGuideline;
use App\Models\CfmMentorProfile;
use App\Models\CfmRankTier;
use App\Models\MentorAssignment;
use App\Models\TeamVisibilityPermission;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\LocationOptions;
use App\Support\MemberDisplayName;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CfmManagementService
{
    private const WEEKLY_SLOT_CAPACITY = 12;

    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
        private readonly CfmRecommendationEngine $recommendations,
        private readonly CfmAssignmentWorkflowService $assignmentWorkflow,
        private readonly CfmTraineeChecklistService $traineeChecklist,
        private readonly ChecklistService $checklists,
    ) {}

    public function rankStructureFor(): array
    {
        return [
            'tiers' => CfmRankTier::query()->active()->get()->values()->all(),
            'guideline' => CfmAdvancementGuideline::query()->active()->value('body'),
        ];
    }

    public function payloadFor(User $viewer): array
    {
        $cfms = $this->accessibleCfms($viewer);
        $fapQueue = $this->fapQueueFor($viewer);
        $cfms = $this->cfmsIncludingAssociateUplines($viewer, $cfms, $fapQueue);
        $recommendationsByAssociate = $this->recommendations->recommendForAssociates($fapQueue, $cfms);

        return [
            'stats' => $this->statsFrom($cfms, $viewer),
            'cfms' => $cfms->values()->all(),
            'assignableAssociates' => collect($fapQueue)->map(fn (array $row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'rank' => $row['rank'],
                'country' => $row['country'] ?? '',
                'province' => $row['province'] ?? '',
                'jurisdictionKey' => $row['jurisdictionKey'] ?? '',
                'locationLabel' => filled($row['province'] ?? null) && filled($row['country'] ?? null)
                    ? LocationOptions::formatJurisdictionLabel($row['country'], $row['province'])
                    : '',
                'uplineCfmIds' => $row['uplineCfmIds'] ?? [],
            ])->values()->all(),
            'fapQueue' => $fapQueue,
            'cfmCandidates' => $this->cfmCandidatesFor($viewer),
            'recommendationsByAssociate' => $recommendationsByAssociate,
            'defaultRecommendationAssociateId' => $fapQueue[0]['id'] ?? null,
            'filterOptions' => [
                'countries' => $cfms->pluck('country')->filter(fn ($v) => $v !== '—')->unique()->sort()->values()->all(),
                'ranks' => $cfms->pluck('rank')->filter(fn ($v) => $v !== '—')->unique()->sort()->values()->all(),
                'timezones' => $cfms->pluck('timezone')->filter(fn ($v) => $v !== '—')->unique()->sort()->values()->all(),
            ],
            'assignUrl' => route('team.cfms.assign'),
            'addCfmUrl' => route('team.cfms.store'),
            'locationOptions' => LocationOptions::forPortal(),
        ];
    }

    public function updateLicensedJurisdictions(User $viewer, User $cfm, array $licensedJurisdictions): array
    {
        if (! $cfm->hasRole('certified-field-mentor')) {
            throw ValidationException::withMessages([
                'licensed_jurisdictions' => 'This member is not a Certified Field Mentor.',
            ]);
        }

        if (! $this->canManageCfm($viewer, $cfm)) {
            abort(403);
        }

        $keys = LocationOptions::normalizeLicensedJurisdictionKeys($licensedJurisdictions);

        $this->syncLicensedJurisdictions($cfm, $keys);

        return [
            'message' => 'Licensed jurisdictions updated for '.$cfm->name.'.',
            'licensedJurisdictions' => $keys,
            'licensedJurisdictionsLabel' => $this->licensedJurisdictionsLabel($keys),
        ];
    }

    /**
     * @return list<string>
     */
    public function licensedJurisdictionKeysFor(User $cfm): array
    {
        $cfm->loadMissing(['profile', 'cfmMentorProfile']);

        return LocationOptions::mergeLicensedJurisdictionKeys(
            $cfm->cfmMentorProfile?->licensed_jurisdictions,
            $cfm->profile?->insurance_licenses,
        );
    }

    /**
     * Keep CFM mentor profile and member profile insurance licenses aligned.
     *
     * @param  list<string>  $keys
     */
    public function syncLicensedJurisdictions(User $cfm, array $keys): void
    {
        if (! $cfm->hasRole('certified-field-mentor')) {
            return;
        }

        $keys = LocationOptions::normalizeLicensedJurisdictionKeys($keys);

        CfmMentorProfile::updateOrCreate(
            ['user_id' => $cfm->id],
            [
                'certification_status' => 'certified',
                'hierarchy_access' => 'my_hierarchy',
                'max_apprentices' => 6,
                'licensed_jurisdictions' => $keys,
                'last_mentor_activity_at' => now(),
            ]
        );

        $cfm->profile()->updateOrCreate(
            ['user_id' => $cfm->id],
            ['insurance_licenses' => $keys]
        );
    }

    private function canManageCfm(User $viewer, User $cfm): bool
    {
        if (! $viewer->canAccessCfmManagement()) {
            return false;
        }

        if ($viewer->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        $myHierarchyIds = $this->hierarchy->descendantsQuery($viewer)->pluck('users.id');
        if ($myHierarchyIds->contains($cfm->id)) {
            return true;
        }

        return TeamVisibilityPermission::query()
            ->where('viewer_id', $viewer->id)
            ->where('visible_user_id', $cfm->id)
            ->exists();
    }

    public function profileFor(User $viewer, User $cfm): array
    {
        $cfm->loadMissing(['profile', 'rank', 'team', 'sponsor', 'cfmMentorProfile']);

        return $this->enrichCfm($cfm, $this->cfmIsInViewerHierarchy($viewer, $cfm));
    }

    public function addCfm(User $viewer, array $data): array
    {
        $candidate = User::query()->whereKey($data['user_id'])->where('is_active', true)->firstOrFail();

        if ($candidate->hasRole('certified-field-mentor')) {
            throw ValidationException::withMessages([
                'user_id' => 'This member is already a Certified Field Mentor.',
            ]);
        }

        if (! $this->hierarchy->canViewMember($viewer, $candidate)) {
            throw ValidationException::withMessages([
                'user_id' => 'You do not have permission to nominate this team member.',
            ]);
        }

        $requireApproval = (bool) ($data['require_approval'] ?? false);
        $targetRank = $data['target_rank'];
        $notes = trim($data['notes'] ?? '');

        return DB::transaction(function () use ($candidate, $requireApproval, $targetRank, $notes): array {
            if (! $candidate->hasRole('certified-field-mentor')) {
                $candidate->assignRole('certified-field-mentor');
            }

            $bio = $notes !== ''
                ? $notes
                : 'Nominated for CFM mentorship duties.';

            CfmMentorProfile::updateOrCreate(
                ['user_id' => $candidate->id],
                [
                    'certification_status' => $requireApproval ? 'pending_approval' : 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'fap_completion_rate' => 0,
                    'calendar_busyness_percent' => 0,
                    'avg_apprentice_progress' => 0,
                    'recommendation_score' => 75,
                    'languages' => ['English'],
                    'specialties' => array_values(array_filter([$targetRank, 'Field Apprenticeship'])),
                    'mentor_bio' => $bio,
                    'last_mentor_activity_at' => now(),
                ]
            );

            $message = $requireApproval
                ? "{$candidate->name} was nominated as a CFM. Certification is pending admin approval and will appear as Pending Approval in the directory."
                : "{$candidate->name} was added as a Certified Field Mentor and is now available for assignments.";

            return [
                'message' => $message,
                'user_id' => $candidate->id,
                'certification_status' => $requireApproval ? 'pending_approval' : 'certified',
            ];
        });
    }

    public function assignAssociate(User $viewer, array $data): array
    {
        $associate = User::query()->whereKey($data['associate_id'])->where('is_active', true)->firstOrFail();
        $cfm = User::query()->role('certified-field-mentor')->whereKey($data['cfm_id'])->where('is_active', true)->firstOrFail();

        if ($associate->mentor_id) {
            throw ValidationException::withMessages([
                'associate_id' => 'This associate already has an assigned CFM.',
            ]);
        }

        if (! $this->hierarchy->canViewMember($viewer, $associate)) {
            throw ValidationException::withMessages([
                'associate_id' => 'You do not have permission to assign this associate.',
            ]);
        }

        if ($associate->id === $cfm->id) {
            throw ValidationException::withMessages([
                'cfm_id' => 'A CFM cannot be assigned as their own trainee.',
            ]);
        }

        $accessibleCfmIds = $this->accessibleCfmIds($viewer);
        $cfmIsAssignable = $accessibleCfmIds->contains($cfm->id)
            || $this->hierarchy->isUplineOf($cfm, $associate);

        if (! $cfmIsAssignable) {
            throw ValidationException::withMessages([
                'cfm_id' => 'You do not have permission to assign to this CFM.',
            ]);
        }

        $this->assertCfmLicensedForAssociate($associate, $cfm);

        $requireCfmApproval = (bool) ($data['require_cfm_approval'] ?? true);

        $startedAt = ! empty($data['start_date']) ? Carbon::parse($data['start_date']) : now();
        $notifyCfm = (bool) ($data['notify_cfm'] ?? true);

        return DB::transaction(function () use ($viewer, $associate, $cfm, $data, $startedAt, $notifyCfm, $requireCfmApproval): array {
            $assignment = MentorAssignment::updateOrCreate(
                [
                    'mentor_id' => $cfm->id,
                    'apprentice_id' => $associate->id,
                    'status' => 'pending',
                ],
                [
                    'assigned_by' => $viewer->id,
                    'started_at' => $startedAt->toDateString(),
                    'completed_at' => ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
                    'confirmed_at' => null,
                ]
            );

            $noteBody = trim(collect([
                $data['reason'] ?? null,
                $data['notes'] ?? null,
            ])->filter()->implode("\n\n"));

            if ($noteBody !== '') {
                DB::table('mentor_notes')->insert([
                    'mentor_assignment_id' => $assignment->id,
                    'created_by' => $viewer->id,
                    'note' => $noteBody,
                    'is_private' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $cfm->loadMissing('cfmMentorProfile');
            $cfmProfile = $cfm->cfmMentorProfile;
            if ($cfmProfile) {
                $cfmProfile->update(['last_mentor_activity_at' => now()]);
            }

            if ($requireCfmApproval) {
                $this->assignmentWorkflow->sendConfirmationRequest($assignment, $notifyCfm);

                $message = "{$associate->name} was submitted to {$cfm->name}. A confirmation email was sent to the CFM. The trainee will appear once the assignment is confirmed.";

                $status = 'pending';
            } else {
                $assignment = $this->assignmentWorkflow->activateAssignment($assignment->fresh(['mentor', 'apprentice.sponsor', 'assignedBy']));

                $message = "{$associate->name} was assigned to {$cfm->name} and is now an active trainee.";

                $status = 'active';
            }

            app(NewMemberValidatedTaskService::class)->completeCfmAssignmentTasksForMember($associate->fresh());

            return [
                'message' => $message,
                'status' => $status,
                'assignment_id' => $assignment->id,
            ];
        });
    }

    public function accessibleCfms(User $viewer): Collection
    {
        $cfmRoleUsers = User::query()
            ->role('certified-field-mentor')
            ->with(['profile', 'rank', 'team', 'sponsor', 'cfmMentorProfile'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $myHierarchyIds = $this->hierarchy->descendantsQuery($viewer)->pluck('users.id');

        $sharedIds = TeamVisibilityPermission::query()
            ->where('viewer_id', $viewer->id)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('visible_user_id');

        return $cfmRoleUsers
            ->filter(function (User $cfm) use ($viewer, $myHierarchyIds, $sharedIds): bool {
                return $this->viewerCanAccessCfm($viewer, $cfm, $myHierarchyIds, $sharedIds);
            })
            ->map(fn (User $cfm) => $this->enrichCfm($cfm, $this->cfmIsInViewerHierarchy($viewer, $cfm)))
            ->sortByDesc('recommendationScore')
            ->values();
    }

    private function accessibleCfmIds(User $viewer): Collection
    {
        $myHierarchyIds = $this->hierarchy->descendantsQuery($viewer)->pluck('users.id');

        $sharedIds = TeamVisibilityPermission::query()
            ->where('viewer_id', $viewer->id)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('visible_user_id');

        return User::query()
            ->role('certified-field-mentor')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->filter(fn (User $cfm) => $this->viewerCanAccessCfm($viewer, $cfm, $myHierarchyIds, $sharedIds))
            ->pluck('id');
    }

    private function cfmIsInViewerHierarchy(User $viewer, User $cfm): bool
    {
        if ($cfm->id === $viewer->id) {
            return true;
        }

        return $this->hierarchy->descendantsQuery($viewer)->where('users.id', $cfm->id)->exists();
    }

    private function viewerCanAccessCfm(User $viewer, User $cfm, Collection $myHierarchyIds, Collection $sharedIds): bool
    {
        if ($cfm->id === $viewer->id) {
            return true;
        }

        if ($viewer->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ($myHierarchyIds->contains($cfm->id)) {
            return true;
        }

        return $sharedIds->contains($cfm->id);
    }

    private function enrichCfm(User $cfm, bool $inMyHierarchy): array
    {
        $profile = $cfm->cfmMentorProfile ?? CfmMentorProfile::make([
            'certification_status' => 'certified',
            'hierarchy_access' => $inMyHierarchy ? 'my_hierarchy' : 'external_cfm',
            'max_apprentices' => 6,
        ]);

        $activeApprentices = $this->apprenticesOfCfmQuery($cfm->id)
            ->where('is_active', true)
            ->count();

        $licensedJurisdictions = $this->licensedJurisdictionKeysFor($cfm);

        $pendingApprentices = $this->mentorAssignmentsForCfmQuery($cfm->id)
            ->where('status', 'pending')
            ->count();

        $completedApprentices = $this->mentorAssignmentsForCfmQuery($cfm->id)
            ->where('status', 'completed')
            ->count();

        $overdueTasks = TaskUser::query()
            ->where('assignee_id', $cfm->id)
            ->where('status', 'overdue')
            ->count();

        $upcomingSessions = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(7))
            ->whereNull('cancelled_at')
            ->count();

        $nextSlot = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->where('starts_at', '>=', now())
            ->whereNull('cancelled_at')
            ->orderBy('starts_at')
            ->value('starts_at');

        $maxApprentices = (int) $profile->max_apprentices;
        $loadPercent = $maxApprentices > 0
            ? (int) round(($activeApprentices / $maxApprentices) * 100)
            : 0;

        $calendarBusyness = $this->calendarBusynessPercent($cfm->id);
        $fapCompletionRate = $this->fapCompletionRateFor($cfm->id);
        $avgApprenticeProgress = $this->avgApprenticeProgressFor($cfm->id);
        $workload = $this->resolveWorkload($activeApprentices, (bool) $profile->manual_unavailable);
        $hierarchySource = $this->hierarchySourceLabel($profile->hierarchy_access, $inMyHierarchy);
        $recommendation = $this->recommendationBand(
            $profile,
            $activeApprentices,
            $overdueTasks,
            $loadPercent,
            $calendarBusyness
        );

        $apprenticeUsers = $this->apprenticesOfCfmQuery($cfm->id)
            ->with(['rank', 'apprenticeshipAssignments' => fn ($q) => $q->where('mentor_id', $cfm->id)->latest()->limit(1)])
            ->limit(8)
            ->get();

        $activeAssignments = $this->mentorAssignmentsForCfmQuery($cfm->id)
            ->whereIn('apprentice_id', $apprenticeUsers->pluck('id'))
            ->where('status', 'active')
            ->latest('id')
            ->get()
            ->unique('apprentice_id')
            ->keyBy('apprentice_id');

        $checklistPercents = $this->traineeChecklist->progressPercentsForAssignments(
            $activeAssignments->pluck('id')
        );

        $apprenticeNames = $apprenticeUsers->map(function (User $a) use ($activeAssignments, $checklistPercents) {
            $assignment = $activeAssignments->get($a->id);

            return [
                'id' => $a->id,
                'name' => $a->name,
                'rank' => $a->rank?->code ?? '—',
                'status' => $a->apprenticeshipAssignments->first()?->status ?? 'active',
                'assignmentId' => $assignment?->id,
                'needsFirstContact' => $assignment && ! $assignment->first_contact_sent_at,
                'checklistPercent' => $assignment ? ($checklistPercents[$assignment->id] ?? 0) : null,
            ];
        });

        $pendingAssignmentRows = $this->mentorAssignmentsForCfmQuery($cfm->id)
            ->where('status', 'pending')
            ->with(['apprentice.rank', 'assignedBy'])
            ->latest('id')
            ->get()
            ->filter(fn (MentorAssignment $assignment) => $assignment->apprentice_id !== $cfm->id)
            ->map(fn (MentorAssignment $assignment) => [
                'id' => $assignment->id,
                'name' => $assignment->apprentice->name,
                'rank' => $assignment->apprentice->rank?->code ?? '—',
                'assignedBy' => $assignment->assignedBy?->name ?? '—',
                'startedAt' => $assignment->started_at?->format('M j, Y') ?? '—',
            ])
            ->values()
            ->all();

        return [
            'id' => $cfm->id,
            'name' => $cfm->name,
            'email' => $cfm->email,
            'phone' => $cfm->profile?->phone ?? '—',
            'initials' => $this->initials($cfm->name),
            'rank' => $cfm->rank?->code ?? '—',
            'rankName' => $cfm->rank?->name ?? '—',
            'hierarchySource' => $hierarchySource,
            'hierarchyAccess' => $profile->hierarchy_access,
            'hierarchyNotice' => $hierarchySource === 'External CFM'
                ? 'This CFM belongs to another hierarchy. Assignment may require permission or approval.'
                : null,
            'sponsor' => $cfm->sponsor?->name ?? '—',
            'agencyOwner' => $this->agencyOwnerFor($cfm),
            'country' => $cfm->profile?->country ?? '—',
            'province' => $cfm->profile?->province ?? '—',
            'city' => $cfm->profile?->city ?? '—',
            'timezone' => $cfm->profile?->timezone ?? '—',
            'certificationStatus' => str($profile->certification_status)->replace('_', ' ')->title()->toString(),
            'activeApprentices' => $activeApprentices,
            'pendingApprentices' => $pendingApprentices,
            'completedApprentices' => $completedApprentices,
            'maxApprentices' => $maxApprentices,
            'loadPercent' => min($loadPercent, 100),
            'calendarBusyness' => $calendarBusyness,
            'fapCompletionRate' => $fapCompletionRate,
            'avgApprenticeProgress' => $avgApprenticeProgress,
            'overdueTasks' => $overdueTasks,
            'upcomingSessions' => $upcomingSessions,
            'nextSlot' => $nextSlot ? Carbon::parse($nextSlot)->format('M j, g:i A') : 'No slot listed',
            'nextSlots' => $this->nextOpenSlots($cfm->id),
            'workloadStatus' => $workload['label'],
            'workloadKey' => $workload['key'],
            'workloadColor' => $workload['color'],
            'recommendationScore' => (int) $profile->recommendation_score,
            'recommendationBand' => $recommendation['band'],
            'recommendationColor' => $recommendation['color'],
            'languages' => $profile->languages ?? [],
            'specialties' => $profile->specialties ?? [],
            'licensedJurisdictions' => $licensedJurisdictions,
            'licensedJurisdictionsLabel' => $this->licensedJurisdictionsLabel($licensedJurisdictions),
            'bio' => $profile->mentor_bio,
            'lastActivity' => $profile->last_mentor_activity_at?->diffForHumans() ?? '—',
            'inMyHierarchy' => $inMyHierarchy,
            'limitedVisibility' => $profile->hierarchy_access === 'limited_visibility',
            'apprentices' => $apprenticeNames->values()->all(),
            'pendingAssignmentRows' => $pendingAssignmentRows,
            'apprenticeBreakdown' => $this->apprenticeBreakdownFor($cfm->id),
            'calendarPreview' => $this->calendarPreview($cfm->id),
            'shareCalendarWithApprentices' => (bool) ($profile->share_calendar_with_apprentices ?? true),
            'shareCalendarWithAgencyOwner' => (bool) ($profile->share_calendar_with_agency_owner ?? false),
            'activityTimeline' => $this->activityTimeline($cfm),
            'assignmentHistory' => $this->mentorAssignmentsForCfmQuery($cfm->id)
                ->with('apprentice:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (MentorAssignment $a) => [
                    'apprentice' => $a->apprentice?->name ?? 'Associate',
                    'status' => str($a->status)->title()->toString(),
                    'date' => ($a->started_at ?? $a->created_at)?->format('M j, Y') ?? '—',
                ])
                ->values()
                ->all(),
            'profileUrl' => route('team.member', $cfm),
            'calendarUrl' => route('calendar.index'),
            'messageUrl' => 'mailto:'.$cfm->email,
        ];
    }

    private function statsFrom(Collection $cfms, User $viewer): array
    {
        $activeApprenticeTotal = $cfms->sum('activeApprentices');
        $cfmCount = $cfms->count();

        $pendingFap = $this->hierarchy->descendantsQuery($viewer)
            ->whereNull('mentor_id')
            ->where('is_active', true)
            ->count();

        $avgSlots = $cfmCount > 0
            ? $cfms->avg(fn (array $cfm) => $cfm['calendarPreview']['slotsThisWeek'] ?? 0)
            : 0;

        return [
            'total' => $cfmCount,
            'available' => $cfms->where('workloadKey', 'available')->count(),
            'moderate' => $cfms->where('workloadKey', 'moderate')->count(),
            'busy' => $cfms->where('workloadKey', 'busy')->count(),
            'overloaded' => $cfms->where('workloadKey', 'overloaded')->count(),
            'myHierarchy' => $cfms->where('inMyHierarchy', true)->count(),
            'externalHierarchy' => $cfms->where('inMyHierarchy', false)->count(),
            'activeApprentices' => $activeApprenticeTotal,
            'pendingFap' => $pendingFap,
            'averageLoad' => $cfmCount > 0 ? round($activeApprenticeTotal / $cfmCount, 1) : 0,
            'fapCompletionRate' => $cfmCount > 0 ? round($cfms->avg('fapCompletionRate'), 1) : 0,
            'avgWeeklyAvailabilityHours' => (int) round($avgSlots * 1.5),
        ];
    }

    private function fapQueueFor(User $viewer): array
    {
        return $this->hierarchy->descendantsQuery($viewer)
            ->whereNull('mentor_id')
            ->where('is_active', true)
            ->whereDoesntHave('apprenticeshipAssignments', fn (Builder $query) => $query->where('status', 'pending'))
            ->with(['rank', 'profile', 'sponsor'])
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => MemberDisplayName::for($user),
                'queueLabel' => MemberDisplayName::fapQueueLabelFor($user),
                'email' => $user->email,
                'rank' => $user->rank?->code ?? '—',
                'rankName' => $user->rank?->name ?? '—',
                'sponsor' => $user->sponsor?->name ?? '—',
                'city' => $user->profile?->city ?? '—',
                'country' => $user->profile?->country ?? '',
                'province' => $user->profile?->province ?? '',
                'jurisdictionKey' => filled($user->profile?->country) && filled($user->profile?->province)
                    ? LocationOptions::jurisdictionKey($user->profile->country, $user->profile->province)
                    : '',
                'timezone' => $user->profile?->timezone ?? '—',
                'uplineCfmIds' => $this->uplineCfmIdsFor($user)->values()->all(),
                'profileUrl' => route('team.member', $user),
            ])
            ->values()
            ->all();
    }

    private function cfmCandidatesFor(User $viewer): array
    {
        return $this->hierarchy->descendantsQuery($viewer)
            ->where('is_active', true)
            ->with(['rank', 'profile'])
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->filter(fn (User $user) => ! $user->hasRole('certified-field-mentor'))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rank' => $user->rank?->code ?? '—',
                'rankName' => $user->rank?->name ?? '—',
                'city' => $user->profile?->city ?? '—',
            ])
            ->values()
            ->all();
    }

    private function apprenticesOfCfmQuery(int $cfmId): Builder
    {
        return User::query()
            ->where('mentor_id', $cfmId)
            ->whereKeyNot($cfmId);
    }

    private function mentorAssignmentsForCfmQuery(int $cfmId): Builder
    {
        return MentorAssignment::query()
            ->where('mentor_id', $cfmId)
            ->where('apprentice_id', '!=', $cfmId);
    }

    private function apprenticeBreakdownFor(int $cfmId): array
    {
        $activeIds = $this->apprenticesOfCfmQuery($cfmId)
            ->where('is_active', true)
            ->pluck('id');

        $activeCount = $activeIds->count();

        $newThisMonth = $this->mentorAssignmentsForCfmQuery($cfmId)
            ->where('started_at', '>=', now()->startOfMonth())
            ->count();

        $nearingCompletion = $this->mentorAssignmentsForCfmQuery($cfmId)
            ->where('status', 'active')
            ->where('started_at', '<=', now()->subDays(75))
            ->count();

        $fapChecklistIds = Checklist::query()
            ->forTypeCode('fap')
            ->active()
            ->pluck('id');

        $behindSchedule = $activeIds->isEmpty() || $fapChecklistIds->isEmpty()
            ? 0
            : (int) ChecklistProgress::query()
                ->whereIn('user_id', $activeIds)
                ->memberProgress()
                ->whereIn('checklist_id', $fapChecklistIds)
                ->whereIn('status', ['pending_confirmation', 'not_started'])
                ->where('updated_at', '<', now()->subDays(14))
                ->distinct()
                ->count('user_id');

        $noRecentActivity = User::query()
            ->whereIn('id', $activeIds)
            ->where('updated_at', '<', now()->subDays(14))
            ->count();

        $awaitingApproval = $this->mentorAssignmentsForCfmQuery($cfmId)
            ->where('status', 'pending')
            ->count();

        return [
            'active' => $activeCount,
            'newThisMonth' => $newThisMonth,
            'nearingCompletion' => $nearingCompletion,
            'behindSchedule' => $behindSchedule,
            'noRecentActivity' => $noRecentActivity,
            'awaitingApproval' => $awaitingApproval,
        ];
    }

    private function fapCompletionRateFor(int $cfmId): float
    {
        $completed = $this->mentorAssignmentsForCfmQuery($cfmId)
            ->where('status', 'completed')
            ->count();

        $active = $this->mentorAssignmentsForCfmQuery($cfmId)
            ->where('status', 'active')
            ->count();

        $total = $completed + $active;

        if ($total === 0) {
            return 0.0;
        }

        return round(($completed / $total) * 100, 1);
    }

    private function avgApprenticeProgressFor(int $cfmId): int
    {
        $apprenticeIds = $this->apprenticesOfCfmQuery($cfmId)->pluck('id');

        if ($apprenticeIds->isEmpty()) {
            return 0;
        }

        $stepCount = Checklist::query()
            ->forTypeCode('fap')
            ->active()
            ->count();

        if ($stepCount === 0) {
            return 0;
        }

        $completedSteps = (int) ChecklistProgress::query()
            ->whereIn('user_id', $apprenticeIds)
            ->memberProgress()
            ->whereIn('checklist_id', $this->checklists->activeChecklistIdsForType('fap'))
            ->completed()
            ->count();

        return (int) round(($completedSteps / ($apprenticeIds->count() * $stepCount)) * 100);
    }

    private function calendarBusynessPercent(int $cfmId): int
    {
        $booked = Booking::query()
            ->where('cfm_id', $cfmId)
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNull('cancelled_at')
            ->count();

        return (int) min(100, round(($booked / self::WEEKLY_SLOT_CAPACITY) * 100));
    }

    private function resolveWorkload(int $active, bool $manualUnavailable): array
    {
        if ($manualUnavailable) {
            return ['key' => 'unavailable', 'label' => 'Unavailable', 'color' => 'slate'];
        }

        return match (true) {
            $active <= 2 => ['key' => 'available', 'label' => 'Available', 'color' => 'emerald'],
            $active <= 5 => ['key' => 'moderate', 'label' => 'Moderate', 'color' => 'amber'],
            $active <= 8 => ['key' => 'busy', 'label' => 'Busy', 'color' => 'orange'],
            default => ['key' => 'overloaded', 'label' => 'Overloaded', 'color' => 'red'],
        };
    }

    private function hierarchySourceLabel(string $access, bool $inMyHierarchy): string
    {
        if ($inMyHierarchy) {
            return 'My Hierarchy';
        }

        return match ($access) {
            'shared_access' => 'Shared Access',
            'admin_approved' => 'Admin Approved',
            'limited_visibility' => 'Limited Visibility',
            default => 'External CFM',
        };
    }

    private function recommendationBand(
        CfmMentorProfile $profile,
        int $active,
        int $overdue,
        int $loadPercent,
        int $calendarBusyness
    ): array {
        $score = (int) $profile->recommendation_score;

        if ($score >= 85 && $active <= 5 && $overdue === 0) {
            return ['band' => 'Recommended', 'color' => 'emerald'];
        }

        if ($score >= 70 && $loadPercent <= 80) {
            return ['band' => 'Good Fit', 'color' => 'amber'];
        }

        if ($score >= 50 && $loadPercent <= 100) {
            return ['band' => 'Use Caution', 'color' => 'orange'];
        }

        return ['band' => 'Not Recommended', 'color' => 'red'];
    }

    private function nextOpenSlots(int $cfmId): array
    {
        return Booking::query()
            ->where('cfm_id', $cfmId)
            ->where('starts_at', '>=', now())
            ->whereNull('cancelled_at')
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map(fn (Booking $b) => Carbon::parse($b->starts_at)->format('M j, g:i A'))
            ->values()
            ->all();
    }

    private function calendarPreview(int $cfmId): array
    {
        $booked = Booking::query()
            ->where('cfm_id', $cfmId)
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNull('cancelled_at')
            ->count();

        $busyness = $this->calendarBusynessPercent($cfmId);

        return [
            'slotsThisWeek' => max(0, self::WEEKLY_SLOT_CAPACITY - $booked),
            'bookedSessions' => $booked,
            'blockedDays' => $booked > 8 ? 2 : 0,
            'conflictWarning' => $booked > 10,
            'avgWeeklyAvailability' => max(0, 100 - $busyness),
        ];
    }

    private function activityTimeline(User $cfm): array
    {
        $items = [];

        $lastAssignment = MentorAssignment::query()->where('mentor_id', $cfm->id)->latest()->first();
        if ($lastAssignment) {
            $items[] = ['label' => 'Apprentice assignment updated', 'time' => $lastAssignment->updated_at?->diffForHumans() ?? '—'];
        }

        $lastBooking = Booking::query()->where('cfm_id', $cfm->id)->latest('starts_at')->first();
        if ($lastBooking) {
            $items[] = ['label' => 'Mentor session scheduled', 'time' => $lastBooking->starts_at?->diffForHumans() ?? '—'];
        }

        $lastTask = TaskUser::query()->where('assignee_id', $cfm->id)->latest()->first();
        if ($lastTask) {
            $items[] = ['label' => 'Mentor task: '.$lastTask->displayTitle(), 'time' => $lastTask->updated_at?->diffForHumans() ?? '—'];
        }

        if ($cfm->cfmMentorProfile?->updated_at) {
            $items[] = ['label' => 'CFM profile updated', 'time' => $cfm->cfmMentorProfile->updated_at->diffForHumans()];
        }

        return $items;
    }

    private function uplineCfmIdsFor(User $associate): Collection
    {
        $ancestorIds = $this->hierarchy->uplineUserIds($associate);

        if ($ancestorIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->role('certified-field-mentor')
            ->whereIn('id', $ancestorIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * @param  list<array<string, mixed>>  $fapQueue
     */
    private function cfmsIncludingAssociateUplines(User $viewer, Collection $cfms, array $fapQueue): Collection
    {
        $existingIds = $cfms->pluck('id');
        $uplineIds = collect($fapQueue)
            ->flatMap(fn (array $row) => $row['uplineCfmIds'] ?? [])
            ->unique()
            ->reject(fn ($id) => $existingIds->contains((int) $id));

        if ($uplineIds->isEmpty()) {
            return $this->annotateTraineeUplineCfms($cfms, $fapQueue);
        }

        $merged = $cfms->merge(
            User::query()
                ->role('certified-field-mentor')
                ->whereIn('id', $uplineIds)
                ->with(['profile', 'rank', 'team', 'sponsor', 'cfmMentorProfile'])
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get()
                ->map(fn (User $cfm) => $this->enrichCfm($cfm, $this->cfmIsInViewerHierarchy($viewer, $cfm)))
        );

        return $this->annotateTraineeUplineCfms($merged->unique('id')->values(), $fapQueue);
    }

    /**
     * @param  list<array<string, mixed>>  $fapQueue
     */
    private function annotateTraineeUplineCfms(Collection $cfms, array $fapQueue): Collection
    {
        $uplineMap = [];

        foreach ($fapQueue as $associate) {
            foreach ($associate['uplineCfmIds'] ?? [] as $cfmId) {
                $uplineMap[(int) $cfmId][] = (int) $associate['id'];
            }
        }

        return $cfms->map(function (array $cfm) use ($uplineMap): array {
            $traineeIds = $uplineMap[(int) $cfm['id']] ?? [];

            if ($traineeIds === []) {
                return $cfm;
            }

            $cfm['isTraineeUpline'] = true;
            $cfm['traineeUplineForAssociateIds'] = array_values(array_unique($traineeIds));

            if (! ($cfm['inMyHierarchy'] ?? false)) {
                $cfm['hierarchySource'] = 'Trainee Upline';
                $cfm['hierarchyNotice'] = 'Licensed upline mentor in the trainee\'s sponsorship branch.';
            }

            return $cfm;
        });
    }

    private function agencyOwnerFor(User $cfm): string
    {
        return $this->resolveAgencyOwnerUpline($cfm)?->name ?? '—';
    }

    private function resolveAgencyOwnerUpline(User $cfm): ?User
    {
        $cfm->loadMissing(['sponsor', 'team.owner']);

        if ($cfm->hasRole('agency-owner')) {
            return $cfm;
        }

        $visited = [$cfm->id];
        $sponsor = $cfm->sponsor;

        while ($sponsor) {
            if ($sponsor->hasRole('agency-owner')) {
                return $sponsor;
            }

            if (in_array($sponsor->id, $visited, true) || ! $sponsor->sponsor_id) {
                break;
            }

            $visited[] = $sponsor->id;
            $sponsor = User::query()->whereKey($sponsor->sponsor_id)->first();
        }

        $teamOwner = $cfm->team?->owner;

        if ($teamOwner?->hasRole('agency-owner')) {
            return $teamOwner;
        }

        return $teamOwner;
    }

    private function assertCfmLicensedForAssociate(User $associate, User $cfm): void
    {
        $associate->loadMissing('profile');
        $cfm->loadMissing('cfmMentorProfile');

        $country = $associate->profile?->country;
        $province = $associate->profile?->province;

        if (! filled($country) || ! filled($province)) {
            throw ValidationException::withMessages([
                'associate_id' => 'The associate must have a country and province/state on their profile before CFM assignment.',
            ]);
        }

        $licensed = $this->licensedJurisdictionKeysFor($cfm);

        if (! LocationOptions::cfmCoversJurisdiction($licensed, $country, $province)) {
            $location = LocationOptions::formatJurisdictionLabel($country, $province);

            throw ValidationException::withMessages([
                'cfm_id' => "This CFM is not licensed in {$location}. Select a CFM licensed in the associate's province or state.",
            ]);
        }
    }

    /**
     * @param  list<string>|null  $keys
     */
    private function licensedJurisdictionsLabel(?array $keys): string
    {
        $keys = LocationOptions::normalizeLicensedJurisdictionKeys($keys);

        if ($keys === []) {
            return '—';
        }

        return implode('; ', LocationOptions::labelsForJurisdictionKeys($keys));
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', $name))->filter()->take(2)
            ->map(fn (string $part) => str($part)->substr(0, 1)->upper()->toString())
            ->join('');
    }
}
