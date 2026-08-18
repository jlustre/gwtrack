<?php

namespace App\Livewire\Fna;

use App\Services\Fna\FnaAnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaDashboard extends Component
{
    public function render(FnaAnalyticsService $analytics): View
    {
        $user = auth()->user();
        $summary = $analytics->summaryFor($user);
        $progress = $analytics->completionProgress($user);
        $trends = $analytics->trendLines($user);

        return view('livewire.fna.fna-dashboard', [
            'summary' => $summary,
            'awaitingReview' => $analytics->awaitingReviewList($user),
            'revisionRequested' => $analytics->revisionRequestedList($user),
            'meetings' => $analytics->meetingsThisWeek($user),
            'progress' => $progress,
            'trends' => $trends,
            'chartColors' => config('fna.analytics_chart_colors', []),
            'trendMax' => max(1, collect($trends)->max(fn (array $row) => max($row['total_fnas'], $row['approved_fnas'], $row['submitted_fnas'])) ?? 1),
            'progressTotal' => max(1, $progress['total']),
        ]);
    }
}
