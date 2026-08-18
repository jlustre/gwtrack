<?php

namespace App\Livewire\Training;

use App\Services\Training\TrainingReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TrainingReports extends Component
{
    public string $periodType = 'monthly';

    public string $scope = 'personal';

    public function mount(TrainingReportService $reports): void
    {
        $available = $reports->availableScopesFor(auth()->user());
        $this->scope = in_array($this->scope, $available, true) ? $this->scope : $available[0];
    }

    public function render(TrainingReportService $reports): View
    {
        $viewer = auth()->user();
        $availableScopes = $reports->availableScopesFor($viewer);

        if (! in_array($this->scope, $availableScopes, true)) {
            $this->scope = $availableScopes[0];
        }

        return view('livewire.training.training-reports', [
            'preview' => $reports->buildReportData($viewer, $this->periodType, $this->scope),
            'availableScopes' => $availableScopes,
            'scopeLabels' => [
                'personal' => 'Personal',
                'directs' => 'Direct Recruits',
                'downline' => 'Team Downline',
                'organization' => 'Organization',
            ],
        ]);
    }
}
