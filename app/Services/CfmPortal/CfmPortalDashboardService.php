<?php

namespace App\Services\CfmPortal;

use App\Models\Booking;
use App\Models\CfmTask;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\CfmManagementService;
use App\Support\MemberDisplayName;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CfmPortalDashboardService
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly CfmManagementService $cfmManagement,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(User $cfm): array
    {
        $trainees = $this->traineesFor($cfm);

        $active = $trainees->where('is_active', true);
        $atRisk = $trainees->where('is_at_risk', true);
        $newAssociates = $trainees->filter(fn (array $row) => $row['is_new']);
        $fapInProgress = $trainees->filter(fn (array $row) => $row['fap_percent'] > 0 && $row['fap_percent'] < 100);
        $licensingInProgress = $trainees->filter(fn (array $row) => $row['licensing_percent'] > 0 && $row['licensing_percent'] < 100);
        $promotionReady = $trainees->where('is_promotion_ready', true);

        $profile = $this->cfmManagement->profileFor($cfm, $cfm);

        return [
            'total_trainees' => $trainees->count(),
            'active_trainees' => $active->count(),
            'fap_in_progress' => $fapInProgress->count(),
            'licensing_in_progress' => $licensingInProgress->count(),
            'new_associates_30d' => $newAssociates->count(),
            'at_risk_trainees' => $atRisk->count(),
            'overdue_tasks' => (int) ($profile['overdueTasks'] ?? 0),
            'upcoming_meetings' => (int) ($profile['upcomingSessions'] ?? 0),
            'fap_graduates' => (int) ($profile['completedApprentices'] ?? 0),
            'promotion_ready' => $promotionReady->count(),
            'pending_approvals' => (int) ($profile['pendingApprentices'] ?? 0),
            'capacity' => [
                'active' => (int) ($profile['activeApprentices'] ?? 0),
                'max' => (int) ($profile['maxApprentices'] ?? 6),
            ],
            'fap_completion_rate' => (float) ($profile['fapCompletionRate'] ?? 0),
            'recommendation_score' => (int) ($profile['recommendationScore'] ?? 0),
            'next_slot' => $profile['nextSlot'] ?? '—',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function traineesFor(User $cfm): Collection
    {
        $fapIds = $this->checklists->activeChecklistIdsForType('fap');
        $licensingIds = $this->checklists->activeChecklistIdsForType('licensing');
        $onboardingIds = $this->checklists->activeChecklistIdsForType('onboarding');

        $traineeUsers = User::query()
            ->where('mentor_id', $cfm->id)
            ->whereKeyNot($cfm->id)
            ->with(['profile', 'rank', 'sponsor'])
            ->orderBy('name')
            ->get();

        $traineeIds = $traineeUsers->pluck('id');

        $assignments = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->whereIn('apprentice_id', $traineeIds)
            ->where('status', 'active')
            ->latest('id')
            ->get()
            ->unique('apprentice_id')
            ->keyBy('apprentice_id');

        $openTaskCounts = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->whereIn('trainee_id', $traineeIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->selectRaw('trainee_id, COUNT(*) as aggregate')
            ->groupBy('trainee_id')
            ->pluck('aggregate', 'trainee_id');

        $overdueTaskCounts = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->whereIn('trainee_id', $traineeIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->selectRaw('trainee_id, COUNT(*) as aggregate')
            ->groupBy('trainee_id')
            ->pluck('aggregate', 'trainee_id');

        $nextMeetings = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->whereIn('trainee_id', $traineeIds)
            ->where('starts_at', '>=', now())
            ->whereNull('cancelled_at')
            ->with('eventType:id,name')
            ->orderBy('starts_at')
            ->get(['id', 'trainee_id', 'starts_at', 'booking_event_type_id'])
            ->unique('trainee_id')
            ->keyBy('trainee_id');

        return $traineeUsers->map(function (User $trainee) use (
            $fapIds,
            $licensingIds,
            $onboardingIds,
            $assignments,
            $openTaskCounts,
            $overdueTaskCounts,
            $nextMeetings,
        ): array {
            $fapPercent = $this->checklists->checklistPercent($fapIds, $trainee->id);
            $licensingPercent = $this->checklists->checklistPercent($licensingIds, $trainee->id);
            $onboardingPercent = $this->checklists->checklistPercent($onboardingIds, $trainee->id);
            $assignment = $assignments->get($trainee->id);
            $nextMeeting = $nextMeetings->get($trainee->id);

            $joinedAt = $trainee->joined_at ?? $trainee->created_at;
            $isNew = $joinedAt && Carbon::parse($joinedAt)->gte(now()->subDays(30));
            $daysSinceActivity = $trainee->updated_at
                ? (int) $trainee->updated_at->diffInDays(now())
                : 999;

            $isAtRisk = $daysSinceActivity >= 7
                || ($fapPercent > 0 && $fapPercent < 40 && $daysSinceActivity >= 5)
                || ($licensingPercent > 0 && $licensingPercent < 30 && $daysSinceActivity >= 5);

            $isPromotionReady = $fapPercent >= 85
                && $licensingPercent >= 85
                && $onboardingPercent >= 90
                && (bool) $trainee->is_active;

            $status = 'active';
            if (! $trainee->is_active) {
                $status = 'inactive';
            } elseif ($isAtRisk) {
                $status = 'at_risk';
            } elseif ($isPromotionReady) {
                $status = 'promotion_ready';
            } elseif ($isNew) {
                $status = 'new';
            } elseif ($licensingPercent > 0 && $licensingPercent < 100) {
                $status = 'licensing';
            } elseif ($fapPercent > 0 && $fapPercent < 100) {
                $status = 'fap';
            }

            $overallPercent = (int) round(($fapPercent + $licensingPercent + $onboardingPercent) / 3);

            return [
                'id' => $trainee->id,
                'assignment_id' => $assignment?->id,
                'name' => MemberDisplayName::for($trainee),
                'email' => $trainee->email,
                'phone' => $trainee->profile?->phone ?: null,
                'rank' => $trainee->rank?->code ?? 'FA',
                'rank_name' => $trainee->rank?->name ?? 'Field Associate',
                'photo_url' => $trainee->profilePhotoUrl(),
                'initials' => $trainee->initials(),
                'sponsor_name' => $trainee->sponsor ? MemberDisplayName::for($trainee->sponsor) : '—',
                'fap_percent' => $fapPercent,
                'licensing_percent' => $licensingPercent,
                'onboarding_percent' => $onboardingPercent,
                'overall_percent' => $overallPercent,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'is_active' => (bool) $trainee->is_active,
                'is_new' => $isNew,
                'is_at_risk' => $isAtRisk,
                'is_promotion_ready' => $isPromotionReady,
                'joined_at' => $joinedAt?->format('M j, Y') ?? '—',
                'joined_raw' => $joinedAt?->toDateString(),
                'city' => $trainee->profile?->city ?? null,
                'province' => $trainee->profile?->province ?? '—',
                'location' => collect([
                    $trainee->profile?->city,
                    $trainee->profile?->province,
                ])->filter()->implode(', ') ?: '—',
                'days_idle' => $daysSinceActivity,
                'last_activity_label' => $daysSinceActivity >= 999
                    ? 'No activity'
                    : ($daysSinceActivity === 0 ? 'Today' : $daysSinceActivity.'d ago'),
                'open_tasks' => (int) ($openTaskCounts[$trainee->id] ?? 0),
                'overdue_tasks' => (int) ($overdueTaskCounts[$trainee->id] ?? 0),
                'next_meeting_at' => $nextMeeting?->starts_at?->format('M j, g:ia'),
                'next_meeting_title' => $nextMeeting?->eventType?->name ?? 'Mentor session',
                'needs_first_contact' => $assignment && ! $assignment->first_contact_sent_at,
                'assignment_started_at' => $assignment?->started_at
                    ? Carbon::parse($assignment->started_at)->format('M j, Y')
                    : null,
            ];
        })->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingAssignmentsFor(User $cfm): array
    {
        return MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'pending')
            ->where('apprentice_id', '!=', $cfm->id)
            ->with(['apprentice.rank', 'assignedBy'])
            ->latest('id')
            ->get()
            ->map(fn (MentorAssignment $assignment) => [
                'id' => $assignment->id,
                'name' => $assignment->apprentice->name,
                'rank' => $assignment->apprentice->rank?->code ?? '—',
                'assigned_by' => $assignment->assignedBy?->name ?? '—',
                'started_at' => $assignment->started_at?->format('M j, Y') ?? '—',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $trainees
     * @return list<string>
     */
    public function aiSuggestionsFor(User $cfm, Collection $trainees): array
    {
        $suggestions = [];

        $atRisk = $trainees->where('is_at_risk', true)->take(3);
        foreach ($atRisk as $trainee) {
            $suggestions[] = "{$trainee['name']} may need follow-up — review activity and schedule a coaching touchpoint.";
        }

        $licensing = $trainees->filter(fn (array $row) => $row['licensing_percent'] > 0 && $row['licensing_percent'] < 50)->take(2);
        foreach ($licensing as $trainee) {
            $suggestions[] = "Licensing progress for {$trainee['name']} is below 50%. Consider a licensing review session.";
        }

        $pending = $this->pendingAssignmentsFor($cfm);
        if ($pending !== []) {
            $suggestions[] = count($pending).' trainee assignment(s) awaiting your confirmation.';
        }

        $upcoming = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(3))
            ->whereNull('cancelled_at')
            ->count();

        if ($upcoming > 0) {
            $suggestions[] = "You have {$upcoming} mentor meeting(s) in the next 3 days.";
        }

        if ($suggestions === []) {
            $suggestions[] = 'Your trainee roster looks healthy. Review promotion-ready associates and schedule proactive coaching.';
        }

        return array_slice($suggestions, 0, 5);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'New',
            'at_risk' => 'At Risk',
            'licensing' => 'Licensing',
            'fap' => 'FAP',
            'promotion_ready' => 'Promotion Ready',
            'inactive' => 'Inactive',
            default => 'Active',
        };
    }
}
