<?php

namespace App\Livewire\Fna;

use App\Services\Fna\FnaAnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AgencyOwnerFnaReports extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view fna agency reports'), 403);
    }

    public function render(FnaAnalyticsService $analytics): View
    {
        $user = auth()->user();
        $report = $analytics->agencyReportFor($user);
        $trends = $analytics->trendLines($user);

        return view('livewire.fna.agency-owner-fna-reports', [
            'report' => $report,
            'trends' => $trends,
            'chartColors' => config('fna.analytics_chart_colors', []),
            'trendMax' => max(1, collect($trends)->max(fn (array $row) => max($row['total_fnas'], $row['approved_fnas'], $row['submitted_fnas'])) ?? 1),
        ]);
    }
}
