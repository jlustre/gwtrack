<?php

namespace App\Services\CfmPortal;

use App\Models\CfmActionPlan;
use App\Models\CfmMeeting;
use App\Models\CfmRiskScore;
use App\Models\CfmTask;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Collection;

class CfmRiskAssessmentService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
        private readonly CfmPortalDashboardService $dashboard,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, bool $refresh = false): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        $current = $refresh
            ? $this->assessAndStore($cfm, $trainee, $rosterRow)
            : $this->latestOrAssess($cfm, $trainee, $rosterRow);

        $history = CfmRiskScore::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->latest('assessed_at')
            ->limit(8)
            ->get()
            ->map(fn (CfmRiskScore $score) => [
                'id' => $score->id,
                'score' => $score->score,
                'level' => $score->level,
                'assessed_at' => $score->assessed_at?->format('M j, Y g:i A'),
                'flag_count' => count($score->flags ?? []),
            ])
            ->values()
            ->all();

        $actionPlans = CfmActionPlan::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with('author')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CfmActionPlan $plan) => $this->actionPlanRow($plan))
            ->values()
            ->all();

        return [
            'key' => 'risk',
            'title' => 'Risk & Action Plans',
            'description' => 'Automated risk scoring with flags, recommended interventions, and structured action plans.',
            'stats' => [
                'score' => $current['score'],
                'level' => $current['level'],
                'flags' => count($current['flags']),
                'active_plans' => collect($actionPlans)->where('status', 'active')->count(),
            ],
            'assessment' => $current,
            'history' => $history,
            'action_plans' => $actionPlans,
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    /**
     * @param  array<string, mixed>  $rosterRow
     * @return array<string, mixed>
     */
    public function latestOrAssess(User $cfm, User $trainee, array $rosterRow = []): array
    {
        $latest = CfmRiskScore::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->latest('assessed_at')
            ->first();

        if ($latest && $latest->assessed_at?->gte(now()->subDay())) {
            return $this->scorePayload($latest);
        }

        return $this->assessAndStore($cfm, $trainee, $rosterRow);
    }

    /**
     * @param  array<string, mixed>  $rosterRow
     * @return array<string, mixed>
     */
    public function assessAndStore(User $cfm, User $trainee, array $rosterRow = []): array
    {
        if ($rosterRow === []) {
            $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        }

        $assessment = $this->compute($trainee, $rosterRow, $cfm);

        $record = CfmRiskScore::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'score' => $assessment['score'],
            'level' => $assessment['level'],
            'flags' => $assessment['flags'],
            'recommended_actions' => $assessment['recommended_actions'],
            'assessed_at' => now(),
        ]);

        return $this->scorePayload($record);
    }

    public function createActionPlan(User $cfm, User $trainee, User $actor, array $data): CfmActionPlan
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }

        return CfmActionPlan::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'steps' => $this->parseLines($data['steps'] ?? null),
            'status' => 'active',
            'target_date' => $data['target_date'] ?? null,
            'created_by' => $actor->id,
        ]);
    }

    public function updateActionPlanStatus(User $cfm, CfmActionPlan $plan, string $status): CfmActionPlan
    {
        $this->assertPlanAccess($cfm, $plan);

        if (! in_array($status, CfmActionPlan::STATUSES, true)) {
            abort(422, 'Invalid action plan status.');
        }

        $plan->update(['status' => $status]);

        return $plan->refresh();
    }

    public function findActionPlanForCfm(User $cfm, int $planId): CfmActionPlan
    {
        return CfmActionPlan::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($planId)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $rosterRow
     * @return array<string, mixed>
     */
    private function compute(User $trainee, array $rosterRow, User $cfm): array
    {
        $flags = [];
        $actions = [];
        $score = 0;

        if ($trainee->updated_at && $trainee->updated_at->lt(now()->subDays(7))) {
            $flags[] = 'No activity in 7+ days';
            $actions[] = 'Schedule an immediate check-in call and review last login activity.';
            $score += 30;
        }

        if (($rosterRow['licensing_percent'] ?? 0) > 0 && ($rosterRow['licensing_percent'] ?? 0) < 40) {
            $flags[] = 'Licensing progress below target';
            $actions[] = 'Review licensing checklist blockers and set weekly licensing goals.';
            $score += 25;
        }

        if (($rosterRow['fap_percent'] ?? 0) > 0 && ($rosterRow['fap_percent'] ?? 0) < 40) {
            $flags[] = 'FAP completion below target';
            $actions[] = 'Open the FAP mentoring checklist and assign focused field tasks.';
            $score += 25;
        }

        if (($rosterRow['onboarding_percent'] ?? 0) < 100 && ($rosterRow['is_new'] ?? false)) {
            $flags[] = 'Onboarding still incomplete';
            $actions[] = 'Walk through remaining onboarding steps in the next coaching session.';
            $score += 15;
        }

        $overdueTasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        if ($overdueTasks > 0) {
            $flags[] = $overdueTasks.' overdue coaching task(s)';
            $actions[] = 'Review overdue tasks and reset priorities with the trainee.';
            $score += min(20, $overdueTasks * 10);
        }

        $recentMeeting = CfmMeeting::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->where('starts_at', '>=', now()->subDays(14))
            ->exists();

        if (! $recentMeeting) {
            $flags[] = 'No coaching meeting in 14 days';
            $actions[] = 'Book a coaching session within the next 3 days.';
            $score += 15;
        }

        $offTrackGoals = Goal::query()
            ->where('user_id', $trainee->id)
            ->where('status', 'off_track')
            ->count();

        if ($offTrackGoals > 0) {
            $flags[] = $offTrackGoals.' off-track goal(s)';
            $actions[] = 'Review goals scorecard and adjust weekly activity targets.';
            $score += min(20, $offTrackGoals * 10);
        }

        $score = min(100, $score);
        $level = $score >= 60 ? 'high' : ($score >= 30 ? 'medium' : 'low');

        if ($flags === []) {
            $actions[] = 'Continue regular coaching cadence and celebrate recent wins.';
        }

        return [
            'score' => $score,
            'level' => $level,
            'flags' => $flags,
            'recommended_actions' => $actions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scorePayload(CfmRiskScore $score): array
    {
        return [
            'id' => $score->id,
            'score' => $score->score,
            'level' => $score->level,
            'flags' => $score->flags ?? [],
            'recommended_actions' => $score->recommended_actions ?? [],
            'assessed_at' => $score->assessed_at?->format('M j, Y g:i A') ?? now()->format('M j, Y g:i A'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actionPlanRow(CfmActionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'summary' => $plan->summary,
            'steps' => $plan->steps ?? [],
            'status' => $plan->status,
            'target_date' => $plan->target_date?->format('M j, Y') ?? '—',
            'author' => $plan->author?->name ?? '—',
            'created_at' => $plan->created_at?->format('M j, Y'),
        ];
    }

    /**
     * @return list<string>
     */
    private function parseLines(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function assertPlanAccess(User $cfm, CfmActionPlan $plan): void
    {
        if ((int) $plan->cfm_id !== (int) $cfm->id) {
            abort(403);
        }
    }
}
