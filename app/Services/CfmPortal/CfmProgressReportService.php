<?php

namespace App\Services\CfmPortal;

use App\Models\CfmNote;
use App\Models\CfmProgressReport;
use App\Models\CfmTask;
use App\Models\Goal;
use App\Models\CfmPromotion;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Support\MemberDisplayName;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class CfmProgressReportService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
        private readonly CfmTraineeProfileService $profiles,
        private readonly DownlineHierarchyService $hierarchy,
        private readonly CfmRiskAssessmentService $riskAssessment,
        private readonly CfmPromotionReadinessService $promotionReadiness,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $history = CfmProgressReport::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with('generator')
            ->latest()
            ->limit(12)
            ->get();

        $preview = $this->buildPayload($cfm, $trainee, 'progress_snapshot');

        return [
            'key' => 'reports',
            'title' => 'Progress Reports',
            'description' => 'Generate trainee progress snapshots, coaching summaries, and promotion readiness reports. Export PDFs and notify trainees.',
            'stats' => [
                'reports_generated' => $history->count(),
                'onboarding' => $preview['progress']['onboarding'] ?? 0,
                'fap' => $preview['progress']['fap'] ?? 0,
                'licensing' => $preview['progress']['licensing'] ?? 0,
                'training' => $preview['progress']['training'] ?? 0,
            ],
            'preview' => $preview,
            'report_types' => CfmProgressReport::TYPES,
            'audiences' => CfmProgressReport::AUDIENCES,
            'history' => $history->map(fn (CfmProgressReport $report) => [
                'id' => $report->id,
                'type' => $report->report_type,
                'type_label' => $report->typeLabel(),
                'audience' => $report->audience,
                'generated_at' => $report->created_at?->format('M j, Y g:i A'),
                'generated_by' => $report->generator?->name ?? '—',
                'download_url' => route('cfm.portal.reports.download', $report),
            ])->values()->all(),
            'roster_export_url' => route('cfm.portal.roster.export'),
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(User $cfm, User $trainee, string $reportType): array
    {
        $profile360 = $this->profiles->profile360($cfm, $trainee->id) ?? [];
        $progress = $this->hierarchy->progressSummary($trainee);

        $openTasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $recentNotes = CfmNote::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (CfmNote $note) => [
                'category' => $note->categoryLabel(),
                'body' => $note->body,
                'created_at' => $note->created_at?->format('M j, Y'),
            ])
            ->values()
            ->all();

        $goals = Goal::query()
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['active', 'off_track', 'completed'])
            ->with('category')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Goal $goal) => [
                'name' => $goal->name,
                'category' => $goal->category?->name ?? 'General',
                'progress' => $goal->progressPercent(),
                'status' => $goal->status,
            ])
            ->values()
            ->all();

        $risk = $this->riskAssessment->latestOrAssess($cfm, $trainee);
        $promotion = $this->promotionReadiness->syncForTrainee($cfm, $trainee);

        return [
            'report_type' => $reportType,
            'report_type_label' => (new CfmProgressReport(['report_type' => $reportType]))->typeLabel(),
            'generated_at' => now()->toIso8601String(),
            'trainee' => [
                'id' => $trainee->id,
                'name' => MemberDisplayName::for($trainee),
                'email' => $trainee->email,
                'rank' => $trainee->rank?->name ?? '—',
                'joined_at' => ($trainee->joined_at ?? $trainee->created_at)?->format('M j, Y') ?? '—',
            ],
            'cfm' => [
                'name' => $cfm->name,
            ],
            'progress' => [
                'onboarding' => $progress['onboarding']['percent'] ?? 0,
                'fap' => $progress['apprenticeship']['percent'] ?? 0,
                'licensing' => $progress['licensing']['percent'] ?? 0,
                'training' => $progress['training']['percent'] ?? 0,
                'rank' => $progress['rank']['percent'] ?? 0,
            ],
            'recruiting' => $profile360['recruiting'] ?? [],
            'goals' => $goals,
            'open_tasks' => $openTasks,
            'recent_notes' => $recentNotes,
            'risk' => [
                'level' => $risk['level'] ?? 'low',
                'score' => $risk['score'] ?? 0,
                'flags' => $risk['flags'] ?? [],
                'recommended_actions' => $risk['recommended_actions'] ?? [],
            ],
            'promotion' => [
                'readiness_percent' => $promotion->readiness_percent,
                'status' => $promotion->status,
                'current_rank' => $promotion->currentRank?->name,
                'target_rank' => $promotion->targetRank?->name,
                'requirements_met' => $promotion->requirements_met ?? [],
                'requirements_remaining' => $promotion->requirements_remaining ?? [],
            ],
            'promotion_ready' => $promotion->status === 'ready' || $promotion->readiness_percent >= 95,
        ];
    }

    public function generate(User $cfm, User $trainee, User $actor, string $reportType, string $audience = 'cfm'): CfmProgressReport
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }

        if (! in_array($reportType, CfmProgressReport::TYPES, true)) {
            abort(422, 'Invalid report type.');
        }

        if (! in_array($audience, CfmProgressReport::AUDIENCES, true)) {
            abort(422, 'Invalid report audience.');
        }

        return CfmProgressReport::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'report_type' => $reportType,
            'audience' => $audience,
            'payload' => $this->buildPayload($cfm, $trainee, $reportType),
            'export_format' => 'pdf',
            'generated_by' => $actor->id,
        ]);
    }

    public function findForCfm(User $cfm, int $reportId): CfmProgressReport
    {
        return CfmProgressReport::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($reportId)
            ->firstOrFail();
    }

    public function renderHtml(CfmProgressReport $report): string
    {
        return view('cfm-portal.reports.pdf', [
            'report' => $report,
            'payload' => $report->payload,
        ])->render();
    }

    public function downloadPdf(User $cfm, CfmProgressReport $report): Response
    {
        if ((int) $report->cfm_id !== (int) $cfm->id) {
            abort(403);
        }

        $pdf = Pdf::loadHTML($this->renderHtml($report))
            ->setPaper('letter', 'portrait');

        $filename = 'cfm-trainee-report-'.$report->trainee_id.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
