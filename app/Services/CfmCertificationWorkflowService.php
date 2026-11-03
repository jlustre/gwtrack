<?php

namespace App\Services;

use App\Models\CfmCertificationRequest;
use App\Models\CfmMentorProfile;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use App\Support\GreatwayRankCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CfmCertificationWorkflowService
{
    /**
     * @return array{eligible: bool, checks: array<string, bool>, missing: list<string>, completed_required: int, total_required: int}
     */
    public function eligibilityFor(User $user): array
    {
        $rank = $user->rank()->first();
        $requiredChecklistIds = Checklist::query()
            ->forTypeCode('cfm-training')
            ->active()
            ->where('is_required', true)
            ->pluck('id');
        $completedRequired = ChecklistProgress::query()
            ->where('user_id', $user->id)
            ->whereNull('mentor_assignment_id')
            ->whereIn('checklist_id', $requiredChecklistIds)
            ->where('status', 'completed')
            ->distinct('checklist_id')
            ->count('checklist_id');

        $checks = [
            'active' => (bool) $user->is_active,
            'rank' => $rank !== null && $rank->isAtLeast(GreatwayRankCatalog::associateCode()),
            'training_started' => app(ChecklistService::class)->hasTypeStarted($user, 'cfm-training'),
            'required_training' => $requiredChecklistIds->isNotEmpty()
                && $completedRequired === $requiredChecklistIds->count(),
            'not_already_certified' => ! $user->hasRole('certified-field-mentor'),
        ];

        $labels = [
            'active' => 'Your account must be active.',
            'rank' => 'You must hold Associate rank or higher.',
            'training_started' => 'Start your CFM Training checklist.',
            'required_training' => 'Complete all required CFM Training checklist items.',
            'not_already_certified' => 'You already have Certified Field Mentor access.',
        ];
        $missing = [];

        foreach ($checks as $key => $passed) {
            if (! $passed) {
                $missing[] = $labels[$key];
            }
        }

        return [
            'eligible' => $missing === [],
            'checks' => $checks,
            'missing' => $missing,
            'completed_required' => $completedRequired,
            'total_required' => $requiredChecklistIds->count(),
        ];
    }

    public function submit(User $user, ?string $notes = null): CfmCertificationRequest
    {
        $this->assertNotesLength($notes, field: 'requestNotes');

        return DB::transaction(function () use ($user, $notes): CfmCertificationRequest {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->assertEligible($lockedUser);

            $pending = CfmCertificationRequest::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();
            if ($pending) {
                throw ValidationException::withMessages([
                    'application' => 'You already have a CFM certification request awaiting review.',
                ]);
            }

            $request = CfmCertificationRequest::query()->create([
                'user_id' => $lockedUser->id,
                'source' => 'self_service',
                'status' => 'pending',
                'request_notes' => filled($notes) ? trim($notes) : null,
            ]);

            $this->notify($request, 'cfm_certification_submitted', [
                'member_name' => $lockedUser->name,
            ]);

            return $request;
        });
    }

    public function nominate(User $candidate, User $nominator, ?string $notes = null): CfmCertificationRequest
    {
        abort_unless($nominator->can('view team'), 403);
        abort_unless($candidate->is_active, 422, 'Only active members may be nominated.');
        $this->assertNotesLength($notes, field: 'notes');

        return DB::transaction(function () use ($candidate, $nominator, $notes): CfmCertificationRequest {
            $lockedCandidate = User::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCandidate->is_active, 422, 'Only active members may be nominated.');

            if ($lockedCandidate->hasRole('certified-field-mentor')) {
                throw ValidationException::withMessages([
                    'user_id' => 'This member is already a Certified Field Mentor.',
                ]);
            }

            if (CfmCertificationRequest::query()
                ->where('user_id', $lockedCandidate->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => 'This member already has a CFM certification request awaiting review.',
                ]);
            }

            $request = CfmCertificationRequest::query()->create([
                'user_id' => $lockedCandidate->id,
                'nominated_by' => $nominator->id,
                'source' => 'nomination',
                'status' => 'pending',
                'request_notes' => filled($notes) ? trim($notes) : null,
            ]);

            $this->notify($request, 'cfm_certification_submitted', [
                'member_name' => $lockedCandidate->name,
            ]);

            return $request;
        });
    }

    /**
     * @return LengthAwarePaginator<int, CfmCertificationRequest>
     */
    public function pendingRequests(): LengthAwarePaginator
    {
        return CfmCertificationRequest::query()
            ->with(['user.rank', 'nominator'])
            ->whereHas('user')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(20);
    }

    /**
     * @return Collection<int, CfmCertificationRequest>
     */
    public function recentRequests(): Collection
    {
        return CfmCertificationRequest::query()
            ->with(['user.rank', 'reviewer', 'nominator'])
            ->whereHas('user')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('reviewed_at')
            ->limit(50)
            ->get();
    }

    public function approve(CfmCertificationRequest $request, User $reviewer, ?string $notes = null): CfmCertificationRequest
    {
        abort_unless($reviewer->can('manage CFM certification'), 403);
        $this->assertNotesLength($notes, field: 'reviewNotes');

        return DB::transaction(function () use ($request, $reviewer, $notes): CfmCertificationRequest {
            $locked = CfmCertificationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'This CFM certification request has already been reviewed.');

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $this->assertEligible($user);

            $user->assignRole('certified-field-mentor');
            $profile = CfmMentorProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                ],
            );
            $profile->forceFill([
                'certification_status' => 'certified',
                'last_mentor_activity_at' => now(),
            ])->save();

            $locked->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'review_notes' => filled($notes) ? trim($notes) : null,
                'reviewed_at' => now(),
            ])->save();

            $this->notify($locked, 'cfm_certification_decision', [
                'decision' => 'approved',
                'review_notes' => $locked->review_notes ?? 'No additional notes were provided.',
            ]);

            return $locked->fresh(['user', 'reviewer', 'nominator']);
        });
    }

    public function reject(CfmCertificationRequest $request, User $reviewer, string $notes): CfmCertificationRequest
    {
        abort_unless($reviewer->can('manage CFM certification'), 403);
        $this->assertNotesLength($notes, required: true, field: 'reviewNotes');

        return DB::transaction(function () use ($request, $reviewer, $notes): CfmCertificationRequest {
            $locked = CfmCertificationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'This CFM certification request has already been reviewed.');

            $locked->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'review_notes' => trim($notes),
                'reviewed_at' => now(),
            ])->save();

            $this->notify($locked, 'cfm_certification_decision', [
                'decision' => 'rejected',
                'review_notes' => $locked->review_notes,
            ]);

            return $locked->fresh(['user', 'reviewer', 'nominator']);
        });
    }

    private function assertEligible(User $user): void
    {
        $eligibility = $this->eligibilityFor($user);
        if (! $eligibility['eligible']) {
            throw ValidationException::withMessages([
                'application' => implode(' ', $eligibility['missing']),
            ]);
        }
    }

    private function assertNotesLength(?string $notes, bool $required = false, string $field = 'review_notes'): void
    {
        $length = mb_strlen(trim((string) $notes));
        if (($required && $length < 5) || $length > 2000) {
            throw ValidationException::withMessages([
                $field => $required
                    ? 'Reviewer feedback must contain between 5 and 2000 characters.'
                    : 'Notes may not exceed 2000 characters.',
            ]);
        }
    }

    /**
     * @param  array<string, string>  $details
     */
    private function notify(CfmCertificationRequest $request, string $trigger, array $details): void
    {
        app(NotificationOrchestrator::class)->dispatch($trigger, [
            'queue' => false,
            'recipients' => [$request->user_id],
            'module' => 'cfm_certification',
            'priority' => 'medium',
            'related' => ['type' => CfmCertificationRequest::class, 'id' => $request->id],
            'related_user_id' => $request->user_id,
            'template_data' => [
                'member_name' => $request->user?->name ?? 'Member',
                ...$details,
            ],
            'action_link' => [
                'route' => 'cfm-training.index',
                'label' => 'View CFM training',
            ],
        ]);
    }
}
