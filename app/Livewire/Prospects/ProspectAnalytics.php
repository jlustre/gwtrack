<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectAnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectAnalytics extends Component
{
    public string $funnelFilter = 'insurance';

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function render(ProspectAnalyticsService $analytics): View
    {
        $user = auth()->user();
        $activityTrend = $analytics->monthlyActivityTrend($user);
        $prospectGrowth = $analytics->prospectGrowth($user);
        $leadSources = $analytics->leadSourceBreakdown($user);

        return view('livewire.prospects.prospect-analytics', [
            'summary' => $analytics->summaryFor($user),
            'performanceReport' => $analytics->performanceReport($user),
            'funnelConversion' => $analytics->funnelConversion($user, $this->funnelFilter),
            'leadSources' => $leadSources,
            'activityTrend' => $activityTrend,
            'prospectGrowth' => $prospectGrowth,
            'dualPipeline' => $analytics->dualPipelineComparison($user),
            'teamAggregates' => $analytics->teamAggregates($user),
            'chartColors' => config('prospects.analytics_chart_colors', []),
            'activityMax' => max(1, collect($activityTrend)->max('total') ?? 1),
            'growthMax' => max(1, collect($prospectGrowth)->max('cumulative') ?? 1),
            'sourceMax' => max(1, collect($leadSources)->max('count') ?? 1),
        ]);
    }
}
