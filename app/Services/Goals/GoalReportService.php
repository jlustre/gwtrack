<?php

namespace App\Services\Goals;

use App\Mail\GoalPerformanceReportMail;
use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\GoalScorecard;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class GoalReportService
{
    public function __construct(
        private readonly GoalDashboardService $dashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(User $user, string $periodType): array
    {
        [$start, $end, $label] = $this->periodBounds($periodType);

        $goals = Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed', 'off_track'])
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('deadline_at', [$start, $end])
                    ->orWhere(fn ($q) => $q->where('starts_at', '<=', $start)->where('deadline_at', '>=', $end));
            })
            ->with(['category', 'milestones'])
            ->orderBy('deadline_at')
            ->get();

        $scorecard = GoalScorecard::query()
            ->where('user_id', $user->id)
            ->where('period_type', $periodType)
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->first();

        $achievements = GoalAchievement::query()
            ->where('user_id', $user->id)
            ->whereBetween('earned_at', [$start, $end])
            ->with('badge')
            ->get();

        $summary = $this->dashboard->summaryFor($user);

        return [
            'user' => $user,
            'period_type' => $periodType,
            'period_label' => $label,
            'period_start' => $start,
            'period_end' => $end,
            'generated_at' => now(),
            'summary' => $summary,
            'goals' => $goals,
            'scorecard' => $scorecard,
            'achievements' => $achievements,
            'completed_count' => $goals->where('status', 'completed')->count(),
            'off_track_count' => $goals->where('status', 'off_track')->count(),
            'average_progress' => $goals->isEmpty() ? 0 : (int) round($goals->avg(fn (Goal $g) => $g->progressPercent())),
        ];
    }

    public function renderHtml(User $user, string $periodType): string
    {
        return view('goals.reports.pdf', $this->buildReportData($user, $periodType))->render();
    }

    public function downloadPdf(User $user, string $periodType): Response
    {
        $pdf = Pdf::loadHTML($this->renderHtml($user, $periodType))
            ->setPaper('letter', 'portrait');

        $filename = 'goal-report-'.$periodType.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function sendEmail(User $user, string $periodType): void
    {
        $data = $this->buildReportData($user, $periodType);

        Mail::to($user->email)->send(new GoalPerformanceReportMail($user, $data));
    }

  /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function periodBounds(string $periodType): array
    {
        return match ($periodType) {
            'weekly' => [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
                'Weekly',
            ],
            'quarterly' => [
                now()->subQuarter()->startOfQuarter(),
                now()->subQuarter()->endOfQuarter(),
                'Quarterly',
            ],
            'annual' => [
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
                'Annual',
            ],
            default => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Monthly',
            ],
        };
    }
}
