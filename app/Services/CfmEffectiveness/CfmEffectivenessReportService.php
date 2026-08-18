<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessReport;
use App\Models\CfmEffectiveness\CfmRecognitionAward;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\User;
use App\Services\CfmManagementService;
use App\Support\MemberDisplayName;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class CfmEffectivenessReportService
{
    public function __construct(
        private readonly CfmEffectivenessMetricsService $metrics,
        private readonly CfmEffectivenessScoreService $scores,
        private readonly CfmEffectivenessFeedbackService $feedback,
        private readonly CfmEffectivenessRiskService $risks,
        private readonly CfmEffectivenessImprovementService $improvements,
        private readonly CfmTraineeSuccessAnalyticsService $successAnalytics,
        private readonly CfmAoEvaluationService $aoEvaluations,
        private readonly CfmManagementService $cfmManagement,
    ) {}

    /**
     * @return array<string, string>
     */
    public function reportTypesFor(User $viewer): array
    {
        $types = config('cfm-effectiveness.report_types', []);

        if (! $viewer->can('view CFM reports')) {
            unset($types['mentor_comparison']);
        }

        return $types;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function periodBounds(string $periodType): array
    {
        $end = now()->endOfDay();

        return match ($periodType) {
            'monthly' => [
                $end->copy()->subMonth()->startOfDay(),
                $end,
                'Monthly',
            ],
            'annual' => [
                $end->copy()->subYear()->startOfDay(),
                $end,
                'Annual',
            ],
            default => [
                $end->copy()->subMonths(3)->startOfDay(),
                $end,
                'Quarterly',
            ],
        };
    }

    public function resolveCfm(User $viewer, ?int $cfmId = null): User
    {
        if ($cfmId && $viewer->can('view CFM reports')) {
            return User::query()->findOrFail($cfmId);
        }

        if ($viewer->hasRole('certified-field-mentor')) {
            return $viewer;
        }

        abort_unless($viewer->can('view CFM reports') || $viewer->can('manage CFM evaluations'), 403);

        return $viewer;
    }

    public function authorizeViewer(User $viewer, User $cfm): void
    {
        if ($viewer->id === $cfm->id) {
            abort_unless($viewer->can('view CFM effectiveness'), 403);

            return;
        }

        abort_unless($viewer->can('view CFM reports') || $viewer->can('manage CFM evaluations'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    public function centerFor(User $viewer, ?User $cfm = null): array
    {
        $cfm ??= $this->resolveCfm($viewer);
        $this->authorizeViewer($viewer, $cfm);

        $history = CfmEffectivenessReport::query()
            ->where('cfm_id', $cfm->id)
            ->with('generator')
            ->latest()
            ->limit(12)
            ->get();

        return [
            'cfm' => [
                'id' => $cfm->id,
                'name' => MemberDisplayName::for($cfm),
            ],
            'report_types' => $this->reportTypesFor($viewer),
            'periods' => CfmEffectivenessReport::PERIODS,
            'history' => $history->map(fn (CfmEffectivenessReport $report) => [
                'id' => $report->id,
                'type' => $report->report_type,
                'type_label' => $report->typeLabel(),
                'period_label' => $report->period_type,
                'generated_at' => $report->created_at?->format('M j, Y g:i A'),
                'generated_by' => $report->generator?->name ?? '—',
                'download_url' => route('cfm.effectiveness.reports.download', $report),
            ])->values()->all(),
            'can_select_cfm' => $viewer->can('view CFM reports'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(User $viewer, User $cfm, string $reportType, string $periodType): array
    {
        $this->authorizeViewer($viewer, $cfm);

        if (! array_key_exists($reportType, $this->reportTypesFor($viewer))) {
            abort(422, 'Invalid report type.');
        }

        if (! in_array($periodType, CfmEffectivenessReport::PERIODS, true)) {
            abort(422, 'Invalid report period.');
        }

        [$periodStart, $periodEnd, $periodLabel] = $this->periodBounds($periodType);
        $profile = $this->cfmManagement->profileFor($cfm, $viewer);
        $periodMetrics = $this->metrics->calculateFor($cfm, $periodStart, $periodEnd);
        $objectiveScore = $this->metrics->weightedObjectiveScore($periodMetrics);
        $feedbackScore = $this->scores->feedbackScoreFor($cfm, $periodStart, $periodEnd);
        $aoScore = $this->scores->aoScoreFor($cfm, $periodStart, $periodEnd);
        $weights = config('cfm-effectiveness.scoring');
        $overallScore = round(
            ($objectiveScore * $weights['objective_weight'])
            + ($feedbackScore * $weights['feedback_weight'])
            + ($aoScore * $weights['ao_weight']),
            2,
        );
        $feedback = $this->feedback->aggregatedFeedbackFor($cfm);
        $aoEval = $this->aoEvaluations->latestFor($cfm);
        $openRisks = $this->risks->openRisksFor($cfm);

        $payload = [
            'report_type' => $reportType,
            'report_type_label' => (new CfmEffectivenessReport(['report_type' => $reportType]))->typeLabel(),
            'period_type' => $periodType,
            'period_label' => $periodLabel,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'cfm' => $this->cfmProfileCard($cfm, $profile),
            'effectiveness_score' => $overallScore,
            'score_breakdown' => [
                'objective' => $objectiveScore,
                'feedback' => $feedbackScore,
                'ao' => $aoScore,
            ],
            'objective_metrics' => $this->metricsForReport($reportType, $periodMetrics),
            'trainee_satisfaction' => $feedback['satisfaction_percent'],
            'feedback_summary' => $this->sanitizeFeedback($feedback),
            'ao_rating' => $aoEval?->overall_score,
            'open_coaching_items' => (int) ($profile['overdueTasks'] ?? 0),
            'upcoming_reviews' => CfmReview::query()
                ->where('cfm_id', $cfm->id)
                ->where('status', 'pending')
                ->count(),
            'recommendations' => $this->improvements->recommendationsFor($cfm),
            'risks' => $this->sanitizeRisks($openRisks),
            'success_analytics' => $this->successAnalytics->summaryFor($cfm),
            'badges' => CfmRecognitionAward::query()
                ->where('cfm_id', $cfm->id)
                ->with('badge')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($award) => [
                    'name' => $award->badge?->name ?? 'Recognition',
                    'awarded_at' => $award->awarded_at?->format('M j, Y') ?? '—',
                ])
                ->values()
                ->all(),
        ];

        if ($reportType === 'mentor_comparison' && $viewer->can('view CFM reports')) {
            $payload['agency_overview'] = $this->agencyOverviewSnapshot();
            $payload['leaderboard'] = $this->leaderboardSnapshot();
        }

        return $payload;
    }

    public function generate(
        User $viewer,
        User $cfm,
        string $reportType,
        string $periodType,
        string $audience = 'cfm',
    ): CfmEffectivenessReport {
        $this->authorizeViewer($viewer, $cfm);

        if (! in_array($audience, CfmEffectivenessReport::AUDIENCES, true)) {
            abort(422, 'Invalid report audience.');
        }

        [$periodStart, $periodEnd] = $this->periodBounds($periodType);
        $payload = $this->buildPayload($viewer, $cfm, $reportType, $periodType);
        $this->scores->calculateAndStore($cfm, $periodStart, $periodEnd, $periodType);

        return CfmEffectivenessReport::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => null,
            'report_type' => $reportType,
            'audience' => $audience,
            'period_type' => $periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'payload' => $payload,
            'export_format' => 'pdf',
            'generated_by' => $viewer->id,
        ]);
    }

    public function canViewReport(User $viewer, CfmEffectivenessReport $report): bool
    {
        if ($viewer->id === $report->cfm_id) {
            return $viewer->can('view CFM effectiveness');
        }

        return $viewer->can('view CFM reports') || $viewer->can('manage CFM evaluations');
    }

    public function findReport(User $viewer, int $reportId): CfmEffectivenessReport
    {
        $report = CfmEffectivenessReport::query()->findOrFail($reportId);

        abort_unless($this->canViewReport($viewer, $report), 403);

        return $report;
    }

    public function renderHtml(CfmEffectivenessReport $report): string
    {
        return view('cfm-effectiveness.reports.pdf', [
            'report' => $report,
            'payload' => $report->payload,
        ])->render();
    }

    public function downloadPdf(User $viewer, CfmEffectivenessReport $report): Response
    {
        abort_unless($this->canViewReport($viewer, $report), 403);

        $pdf = Pdf::loadHTML($this->renderHtml($report))
            ->setPaper('letter', 'portrait');

        $slug = str_replace('_', '-', $report->report_type);
        $filename = 'cfm-effectiveness-'.$slug.'-'.$report->cfm_id.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function cfmProfileCard(User $cfm, array $profile): array
    {
        return [
            'id' => $cfm->id,
            'name' => MemberDisplayName::for($cfm),
            'photo_url' => $cfm->profilePhotoUrl(),
            'rank' => $cfm->rank?->name ?? 'Certified Field Mentor',
            'years_experience' => $cfm->joined_at ? (int) $cfm->joined_at->diffInYears(now()) : 0,
            'current_trainees' => (int) ($profile['activeApprentices'] ?? 0),
            'active_trainees' => (int) ($profile['activeApprentices'] ?? 0),
            'graduated_trainees' => (int) ($profile['completedApprentices'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $periodMetrics
     * @return array<string, mixed>
     */
    private function metricsForReport(string $reportType, array $periodMetrics): array
    {
        $focusKeys = match ($reportType) {
            'retention_report' => ['retention_rate'],
            'licensing_report' => ['licensing_completion_rate'],
            'fap_report' => ['fap_completion_rate'],
            default => array_keys($periodMetrics),
        };

        return collect($periodMetrics)
            ->only($focusKeys)
            ->map(fn (array $metric, string $key) => [
                'key' => $key,
                'label' => $metric['label'] ?? $key,
                'score' => $metric['score'] ?? 0,
                'value' => $metric['value'] ?? null,
                'detail' => isset($metric['meta']) ? json_encode($metric['meta']) : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function agencyOverviewSnapshot(): array
    {
        $cfms = User::role('certified-field-mentor')->get();
        $scores = $cfms->map(function (User $cfm): float {
            $metrics = $this->metrics->calculateFor($cfm);

            return $this->metrics->weightedObjectiveScore($metrics);
        });
        $atRisk = $cfms->filter(fn (User $cfm) => $this->risks->openRisksFor($cfm)->isNotEmpty());

        return [
            'cfm_count' => $cfms->count(),
            'average_effectiveness' => $scores->isEmpty() ? 0 : round($scores->avg(), 1),
            'top_performers' => $this->leaderboardSnapshot()->take(5)->values()->all(),
            'at_risk_cfms' => $atRisk->map(fn (User $cfm) => [
                'id' => $cfm->id,
                'name' => MemberDisplayName::for($cfm),
                'risks' => $this->risks->openRisksFor($cfm)->pluck('message')->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function leaderboardSnapshot(): Collection
    {
        return User::role('certified-field-mentor')
            ->get()
            ->map(function (User $cfm): array {
                $metrics = $this->metrics->calculateFor($cfm);

                return [
                    'cfm_id' => $cfm->id,
                    'name' => MemberDisplayName::for($cfm),
                    'score' => $this->metrics->weightedObjectiveScore($metrics),
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * @param  array<string, mixed>  $feedback
     * @return array<string, mixed>
     */
    private function sanitizeFeedback(array $feedback): array
    {
        return collect($feedback)
            ->except(['responses'])
            ->all();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $risks
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeRisks(Collection|array $risks): array
    {
        return collect($risks)
            ->map(fn ($risk) => is_array($risk) ? $risk : [
                'level' => $risk->severity ?? 'medium',
                'message' => $risk->message ?? (string) $risk,
            ])
            ->values()
            ->all();
    }
}
