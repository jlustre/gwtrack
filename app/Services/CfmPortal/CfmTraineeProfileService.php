<?php

namespace App\Services\CfmPortal;

use App\Models\Goal;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\DownlineHierarchyService;
use App\Services\Goals\GoalCoachingService;
use App\Services\MemberUplineService;
use App\Support\MemberDisplayName;
use Illuminate\Support\Collection;

class CfmTraineeProfileService
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly DownlineHierarchyService $hierarchy,
        private readonly MemberUplineService $upline,
        private readonly GoalCoachingService $goalCoaching,
        private readonly CfmPortalDashboardService $dashboard,
        private readonly CfmRiskAssessmentService $riskAssessment,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function profile360(User $cfm, int $traineeId): ?array
    {
        $trainee = User::query()
            ->whereKey($traineeId)
            ->where('mentor_id', $cfm->id)
            ->whereKeyNot($cfm->id)
            ->with(['profile', 'rank', 'sponsor', 'mentor'])
            ->first();

        if (! $trainee) {
            return null;
        }

        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $traineeId);

        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $trainee->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $metrics = $this->hierarchy->memberMetrics($trainee);
        $progress = $this->hierarchy->progressSummary($trainee);

        $goals = Goal::query()
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['active', 'off_track', 'completed'])
            ->with('category')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Goal $goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'category' => $goal->category?->name ?? 'General',
                'progress' => $goal->progressPercent(),
                'status' => $goal->status,
                'deadline' => $goal->deadline_at?->format('M j, Y'),
            ])
            ->values()
            ->all();

        $agencyOwner = $this->upline->agencyOwner($trainee);

        return [
            'profile' => [
                'id' => $trainee->id,
                'name' => MemberDisplayName::for($trainee),
                'email' => $trainee->email,
                'phone' => $trainee->profile?->phone ?? '—',
                'photo_url' => $trainee->profilePhotoUrl(),
                'initials' => $trainee->initials(),
                'rank' => $trainee->rank?->code ?? 'FA',
                'rank_name' => $trainee->rank?->name ?? '—',
                'target_rank' => $trainee->rank?->name ?? '—',
                'sponsor' => $trainee->sponsor?->name ?? '—',
                'agency_owner' => $agencyOwner?->name ?? '—',
                'cfm' => $trainee->mentor?->name ?? $cfm->name,
                'location' => collect([$trainee->profile?->city, $trainee->profile?->province])->filter()->join(', ') ?: '—',
                'joined_at' => ($trainee->joined_at ?? $trainee->created_at)?->format('M j, Y') ?? '—',
                'licensing_status' => ($rosterRow['licensing_percent'] ?? 0) >= 100 ? 'Complete' : 'In Progress',
                'fap_status' => ($rosterRow['fap_percent'] ?? 0) >= 100 ? 'Complete' : 'In Progress',
            ],
            'assignment_id' => $assignment?->id,
            'progress' => [
                'onboarding' => $progress['onboarding']['percent'] ?? 0,
                'licensing' => $progress['licensing']['percent'] ?? ($rosterRow['licensing_percent'] ?? 0),
                'fap' => $progress['apprenticeship']['percent'] ?? ($rosterRow['fap_percent'] ?? 0),
                'training' => $progress['training']['percent'] ?? 0,
                'rank' => $progress['rank']['percent'] ?? 0,
            ],
            'recruiting' => [
                'direct_recruits' => $metrics['direct_recruits'] ?? 0,
                'total_downline' => $metrics['total_downline'] ?? 0,
                'prospects' => $metrics['prospects'] ?? 0,
            ],
            'goals' => $goals,
            'risk' => $this->riskAssessment->latestOrAssess($cfm, $trainee, $rosterRow ?? []),
            'coaching_suggestions' => $this->goalCoaching->suggestionsFor($trainee),
            'checklist_links' => [
                'onboarding' => route('onboarding.index'),
                'licensing' => route('licensing.index'),
                'fap' => $assignment
                    ? route('cfm.portal.trainees.checklist', $assignment)
                    : null,
            ],
            'quick_actions' => [
                ['label' => 'Send Message', 'action' => 'message', 'style' => 'secondary'],
                ['label' => 'Schedule Meeting', 'action' => 'meeting', 'style' => 'secondary'],
                ['label' => 'View Profile', 'action' => 'profile', 'style' => 'primary'],
                ['label' => 'Create Task', 'action' => 'task', 'style' => 'secondary'],
            ],
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }
}
