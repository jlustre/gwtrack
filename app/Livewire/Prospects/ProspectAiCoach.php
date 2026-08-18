<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectAiCoachService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectAiCoach extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function render(ProspectAiCoachService $coach): View
    {
        $data = $coach->recommendationsFor(auth()->user());

        $grouped = collect($data['recommendations'])
            ->groupBy('priority')
            ->sortKeysUsing(fn (string $a, string $b): int => ['high' => 1, 'medium' => 2, 'low' => 3][$a] <=> ['high' => 1, 'medium' => 2, 'low' => 3][$b]);

        return view('livewire.prospects.prospect-ai-coach', [
            'groupedRecommendations' => $grouped,
            'stalledProspects' => $data['stalled_prospects'],
            'highValueOpportunities' => $data['high_value_opportunities'],
            'scoredProspects' => $data['scored_prospects'],
            'totalCount' => count($data['recommendations']),
        ]);
    }
}
