<?php

namespace App\Services\CfmPortal;

use App\Models\CfmCoachingSession;
use App\Models\CfmNote;
use App\Models\CfmTask;
use App\Models\Goal;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Goals\GoalCoachingService;
use App\Support\MemberDisplayName;
use Illuminate\Support\Collection;

class CfmCoachingAssistantService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
        private readonly CfmPortalDashboardService $dashboard,
        private readonly CfmRiskAssessmentService $risk,
        private readonly CfmPromotionReadinessService $promotion,
        private readonly DownlineHierarchyService $hierarchy,
        private readonly GoalCoachingService $goalCoaching,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId): ?array
    {
        if (! config('cfm-portal.ai_coaching.enabled', true)) {
            return null;
        }

        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        $context = $this->buildContext($cfm, $trainee, $rosterRow);
        $latestSession = $this->latestSession($cfm, $trainee);

        $sessions = CfmCoachingSession::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with('author')
            ->latest('session_at')
            ->limit(8)
            ->get()
            ->map(fn (CfmCoachingSession $session) => $this->sessionRow($session))
            ->values()
            ->all();

        return [
            'key' => 'assistant',
            'title' => 'AI Coaching Assistant',
            'description' => 'Data-driven coaching briefs, trainee Q&A, and recommended next steps powered by live portal metrics.',
            'stats' => [
                'risk_level' => $context['risk']['level'],
                'readiness' => $context['promotion']['readiness_percent'],
                'open_tasks' => $context['open_tasks'],
                'sessions' => count($sessions),
            ],
            'brief' => $latestSession ? $this->sessionRow($latestSession) : null,
            'context' => $context,
            'prompts' => $this->suggestedPrompts($context),
            'sessions' => $sessions,
            'focus_areas' => CfmCoachingSession::FOCUS_AREAS,
            'sms_enabled' => app(CfmSmsService::class)->isEnabled(),
            'sms_templates' => app(CfmSmsService::class)->templateOptions(),
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    /**
     * @param  array<string, mixed>  $rosterRow
     */
    public function generateBrief(User $cfm, User $trainee, User $actor, string $focusArea = 'general'): CfmCoachingSession
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }

        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        $context = $this->buildContext($cfm, $trainee, $rosterRow);
        $analysis = $this->analyzeContext($context, $focusArea);

        return CfmCoachingSession::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'focus_area' => $focusArea,
            'notes' => $analysis['summary'],
            'strengths' => $analysis['strengths'],
            'weaknesses' => $analysis['weaknesses'],
            'recommendations' => $analysis['recommendations'],
            'session_at' => now(),
            'created_by' => $actor->id,
        ]);
    }

    public function answerQuestion(User $cfm, User $trainee, string $question): string
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }

        $rosterRow = $this->dashboard->traineesFor($cfm)->firstWhere('id', $trainee->id) ?? [];
        $context = $this->buildContext($cfm, $trainee, $rosterRow);
        $needle = strtolower(trim($question));

        if ($needle === '') {
            return 'Ask a question about this trainee\'s progress, risk, goals, or promotion readiness.';
        }

        if (str_contains($needle, 'risk') || str_contains($needle, 'at risk') || str_contains($needle, 'falling behind')) {
            return $this->riskAnswer($context);
        }

        if (str_contains($needle, 'promotion') || str_contains($needle, 'ready') || str_contains($needle, 'rank')) {
            return $this->promotionAnswer($context);
        }

        if (str_contains($needle, 'goal')) {
            return $this->goalsAnswer($context);
        }

        if (str_contains($needle, 'fap') || str_contains($needle, 'apprenticeship')) {
            return sprintf(
                'FAP progress is %d%%. %s',
                $context['progress']['fap'],
                $context['progress']['fap'] >= 85
                    ? 'The trainee is in strong shape for field apprenticeship completion.'
                    : 'Schedule field observations and review the mentoring checklist together.'
            );
        }

        if (str_contains($needle, 'licens')) {
            return sprintf(
                'Licensing progress is %d%%. %s',
                $context['progress']['licensing'],
                $context['progress']['licensing'] >= 85
                    ? 'Licensing milestones are nearly complete — confirm exam dates and carrier appointments.'
                    : 'Prioritize licensing checklist items and book a licensing review session.'
            );
        }

        if (str_contains($needle, 'meeting') || str_contains($needle, 'schedule') || str_contains($needle, 'touchpoint')) {
            return 'Based on current metrics, schedule a coaching touchpoint within 3 days. Use the Meetings center to log the session and capture action items afterward.';
        }

        if (str_contains($needle, 'task') || str_contains($needle, 'follow up') || str_contains($needle, 'follow-up')) {
            return sprintf(
                'There are %d open coaching tasks. %s',
                $context['open_tasks'],
                $context['open_tasks'] > 0
                    ? 'Review overdue items first, then assign one high-impact task for this week.'
                    : 'Consider assigning a prospecting or licensing task to maintain momentum.'
            );
        }

        return $this->executiveSummary($context);
    }

    /**
     * @return list<array{type: string, trainee_id: int|null, trainee_name: string|null, message: string}>
     */
    public function rosterPrioritiesFor(User $cfm, Collection $trainees): array
    {
        $priorities = [];

        foreach ($trainees->where('is_at_risk', true)->take(3) as $trainee) {
            $priorities[] = [
                'type' => 'risk',
                'trainee_id' => $trainee['id'],
                'trainee_name' => $trainee['name'],
                'message' => "{$trainee['name']} is flagged at risk — open AI Coach for a full brief and action plan.",
            ];
        }

        foreach ($trainees->where('is_promotion_ready', true)->take(2) as $trainee) {
            $priorities[] = [
                'type' => 'promotion',
                'trainee_id' => $trainee['id'],
                'trainee_name' => $trainee['name'],
                'message' => "{$trainee['name']} appears promotion-ready — review readiness in the Promotion center.",
            ];
        }

        foreach ($trainees->filter(fn (array $row) => $row['licensing_percent'] > 0 && $row['licensing_percent'] < 50)->take(2) as $trainee) {
            $priorities[] = [
                'type' => 'licensing',
                'trainee_id' => $trainee['id'],
                'trainee_name' => $trainee['name'],
                'message' => "Licensing for {$trainee['name']} is below 50% — schedule a licensing review.",
            ];
        }

        if ($priorities === []) {
            $priorities[] = [
                'type' => 'healthy',
                'trainee_id' => null,
                'trainee_name' => null,
                'message' => 'Roster looks healthy. Select a trainee to generate a personalized coaching brief.',
            ];
        }

        return array_slice($priorities, 0, 5);
    }

    private function latestSession(User $cfm, User $trainee): ?CfmCoachingSession
    {
        return CfmCoachingSession::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->latest('session_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $rosterRow
     * @return array<string, mixed>
     */
    private function buildContext(User $cfm, User $trainee, array $rosterRow): array
    {
        $trainee->loadMissing(['profile', 'rank']);
        $progress = $this->hierarchy->progressSummary($trainee);
        $risk = $this->risk->latestOrAssess($cfm, $trainee, $rosterRow);
        $promotionRecord = $this->promotion->syncForTrainee($cfm, $trainee);
        $goalSuggestions = $this->goalCoaching->suggestionsFor($trainee);

        $goals = Goal::query()
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['active', 'off_track', 'completed'])
            ->with('category')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Goal $goal) => [
                'name' => $goal->name,
                'progress' => $goal->progressPercent(),
                'status' => $goal->status,
            ])
            ->values()
            ->all();

        $openTasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $recentNotes = CfmNote::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->latest()
            ->limit(3)
            ->count();

        return [
            'trainee_name' => MemberDisplayName::for($trainee),
            'trainee_rank' => $trainee->rank?->name ?? '—',
            'status' => $rosterRow['status_label'] ?? 'Active',
            'progress' => [
                'onboarding' => $progress['onboarding']['percent'] ?? 0,
                'fap' => $progress['apprenticeship']['percent'] ?? 0,
                'licensing' => $progress['licensing']['percent'] ?? 0,
                'training' => $progress['training']['percent'] ?? 0,
                'rank' => $progress['rank']['percent'] ?? 0,
            ],
            'risk' => $risk,
            'promotion' => [
                'readiness_percent' => $promotionRecord->readiness_percent,
                'status' => $promotionRecord->status,
                'requirements_remaining' => count($promotionRecord->requirements_remaining ?? []),
            ],
            'goals' => $goals,
            'goal_suggestions' => $goalSuggestions,
            'open_tasks' => $openTasks,
            'recent_notes' => $recentNotes,
            'is_at_risk' => (bool) ($rosterRow['is_at_risk'] ?? false),
            'is_promotion_ready' => (bool) ($rosterRow['is_promotion_ready'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{summary: string, strengths: list<string>, weaknesses: list<string>, recommendations: list<string>}
     */
    private function analyzeContext(array $context, string $focusArea): array
    {
        $strengths = [];
        $weaknesses = [];
        $recommendations = [];

        foreach ([
            'onboarding' => 'Onboarding',
            'fap' => 'FAP',
            'licensing' => 'Licensing',
            'training' => 'Training',
        ] as $key => $label) {
            $value = $context['progress'][$key] ?? 0;
            if ($value >= 85) {
                $strengths[] = "{$label} progress is strong at {$value}%.";
            } elseif ($value > 0 && $value < 50) {
                $weaknesses[] = "{$label} is below target at {$value}%.";
                $recommendations[] = "Create an action plan focused on {$label} milestones.";
            }
        }

        if (($context['risk']['level'] ?? 'low') !== 'low') {
            $weaknesses = array_merge($weaknesses, $context['risk']['flags'] ?? []);
            $recommendations = array_merge($recommendations, $context['risk']['recommended_actions'] ?? []);
        } else {
            $strengths[] = 'No critical risk flags detected.';
        }

        if ($context['is_promotion_ready']) {
            $strengths[] = 'Trainee is tracking toward promotion readiness.';
        }

        if ($context['open_tasks'] > 0) {
            $recommendations[] = "Review {$context['open_tasks']} open coaching task(s) and close or reprioritize.";
        }

        foreach ($context['goal_suggestions'] as $suggestion) {
            $recommendations[] = $suggestion;
        }

        if ($recommendations === []) {
            $recommendations[] = 'Maintain weekly coaching cadence and celebrate recent wins.';
        }

        $summary = $this->executiveSummary($context);
        if ($focusArea !== 'general') {
            $summary .= ' Focus area for this brief: '.str_replace('_', ' ', $focusArea).'.';
        }

        return [
            'summary' => $summary,
            'strengths' => array_values(array_unique(array_slice($strengths, 0, 5))),
            'weaknesses' => array_values(array_unique(array_slice($weaknesses, 0, 5))),
            'recommendations' => array_values(array_unique(array_slice($recommendations, 0, 6))),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function suggestedPrompts(array $context): array
    {
        $prompts = [
            'What should I focus on in our next coaching session?',
            'Is this trainee ready for promotion?',
            'Summarize current risk factors.',
        ];

        if ($context['open_tasks'] > 0) {
            $prompts[] = 'Which open tasks need follow-up first?';
        }

        if (($context['progress']['licensing'] ?? 0) < 70) {
            $prompts[] = 'How can I accelerate licensing progress?';
        }

        return array_slice($prompts, 0, 5);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function executiveSummary(array $context): string
    {
        return sprintf(
            '%s (%s) is %s with onboarding %d%%, FAP %d%%, licensing %d%%, and training %d%%. Risk level is %s (score %d). Promotion readiness is %d%%.',
            $context['trainee_name'],
            $context['trainee_rank'],
            strtolower($context['status']),
            $context['progress']['onboarding'],
            $context['progress']['fap'],
            $context['progress']['licensing'],
            $context['progress']['training'],
            $context['risk']['level'],
            $context['risk']['score'],
            $context['promotion']['readiness_percent'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function riskAnswer(array $context): string
    {
        $flags = $context['risk']['flags'] ?? [];

        if ($flags === []) {
            return 'Risk is low. Continue proactive coaching and monitor activity weekly.';
        }

        return 'Risk level is '.$context['risk']['level'].' (score '.$context['risk']['score'].'): '
            .implode(' ', $flags)
            .' Recommended: '.implode(' ', $context['risk']['recommended_actions'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function promotionAnswer(array $context): string
    {
        return sprintf(
            'Promotion readiness is %d%% with status "%s". %d requirement(s) still open. %s',
            $context['promotion']['readiness_percent'],
            $context['promotion']['status'],
            $context['promotion']['requirements_remaining'],
            $context['is_promotion_ready']
                ? 'This trainee is a strong promotion candidate — review the Promotion center and confirm rank requirements.'
                : 'Focus on closing remaining onboarding, FAP, and licensing gaps before nomination.'
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function goalsAnswer(array $context): string
    {
        if ($context['goals'] === []) {
            return 'No active goals found. Encourage the trainee to set performance planner goals or assign a coaching task.';
        }

        $parts = collect($context['goals'])
            ->map(fn (array $goal) => "{$goal['name']} ({$goal['progress']}%, {$goal['status']})")
            ->implode('; ');

        $extra = $context['goal_suggestions'] !== []
            ? ' Suggestions: '.implode(' ', $context['goal_suggestions'])
            : '';

        return 'Active goals: '.$parts.$extra;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionRow(CfmCoachingSession $session): array
    {
        return [
            'id' => $session->id,
            'focus_area' => $session->focus_area,
            'summary' => $session->notes,
            'strengths' => $session->strengths ?? [],
            'weaknesses' => $session->weaknesses ?? [],
            'recommendations' => $session->recommendations ?? [],
            'session_at' => $session->session_at?->format('M j, Y g:i A') ?? $session->created_at?->format('M j, Y g:i A'),
            'author' => $session->author?->name ?? '—',
        ];
    }
}
