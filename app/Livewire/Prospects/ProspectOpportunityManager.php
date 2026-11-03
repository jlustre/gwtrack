<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectOpportunityService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectOpportunityManager extends Component
{
    public Prospect $prospect;

    public string $estimatedValue = '';

    public string $salesProbability = '';

    public ?string $expectedCloseAt = null;

    public string $recruitingProbability = '';

    public ?string $expectedStartAt = null;

    public string $lostReason = '';

    public function mount(Prospect $prospect): void
    {
        $this->authorize('update', $prospect);
        $this->prospect = $prospect->loadMissing(['salesOpportunity', 'recruitingOpportunity']);
        $sales = $this->prospect->salesOpportunity;
        $recruiting = $this->prospect->recruitingOpportunity;

        $this->estimatedValue = (string) ($sales?->estimated_value ?? '');
        $this->salesProbability = (string) ($sales?->probability ?? '');
        $this->expectedCloseAt = $sales?->expected_close_at?->format('Y-m-d');
        $this->recruitingProbability = (string) ($recruiting?->probability ?? '');
        $this->expectedStartAt = $recruiting?->expected_start_at?->format('Y-m-d');
        $this->lostReason = (string) ($this->prospect->lost_reason ?? '');
    }

    public function save(ProspectOpportunityService $opportunities): void
    {
        $opportunities->saveForecast($this->prospect, auth()->user(), [
            'estimated_value' => $this->estimatedValue !== '' ? $this->estimatedValue : null,
            'sales_probability' => $this->salesProbability !== '' ? $this->salesProbability : null,
            'expected_close_at' => $this->expectedCloseAt ?: null,
            'recruiting_probability' => $this->recruitingProbability !== '' ? $this->recruitingProbability : null,
            'expected_start_at' => $this->expectedStartAt ?: null,
            'lost_reason' => $this->lostReason !== '' ? $this->lostReason : null,
        ]);

        session()->flash('opportunity-status', 'Opportunity forecast saved.');
    }

    public function render(): View
    {
        $this->prospect->loadMissing(['salesOpportunity', 'recruitingOpportunity']);

        return view('livewire.prospects.prospect-opportunity-manager', [
            'salesOpportunity' => $this->prospect->salesOpportunity,
            'recruitingOpportunity' => $this->prospect->recruitingOpportunity,
        ]);
    }
}
