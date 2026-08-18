<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectAiCoachService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectAiCoachPanel extends Component
{
    public Prospect $prospect;

    public function mount(Prospect $prospect): void
    {
        $this->authorize('view', $prospect);
        $this->prospect = $prospect;
    }

    public function render(ProspectAiCoachService $coach): View
    {
        $recommendations = collect($coach->recommendationsForProspect($this->prospect))->take(3);
        $intelligence = $coach->intelligenceForProspect($this->prospect->fresh());

        return view('livewire.prospects.prospect-ai-coach-panel', [
            'recommendations' => $recommendations,
            'intelligence' => $intelligence,
        ]);
    }
}
