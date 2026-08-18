<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoalReports extends Component
{
    public string $periodType = 'weekly';

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function render(GoalReportService $reports): View
    {
        return view('livewire.goals.goal-reports', [
            'preview' => $reports->buildReportData(auth()->user(), $this->periodType),
        ]);
    }
}
