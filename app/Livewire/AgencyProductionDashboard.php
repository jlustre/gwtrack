<?php

namespace App\Livewire;

use App\Services\AgencyProductionDashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class AgencyProductionDashboard extends Component
{
    #[Url]
    public string $period = 'ytd';

    #[Url]
    public ?int $member = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        if (! array_key_exists($this->period, config('production-dashboard.periods', []))) {
            $this->period = 'ytd';
        }
    }

    public function updatedPeriod(): void
    {
        if (! array_key_exists($this->period, config('production-dashboard.periods', []))) {
            $this->period = 'ytd';
        }
    }

    public function clearMemberFilter(): void
    {
        $this->member = null;
    }

    public function render(AgencyProductionDashboardService $dashboard): View
    {
        $viewer = auth()->user();
        $member = $dashboard->resolveMember($viewer, $this->member);

        return view('livewire.agency-production-dashboard', [
            'dashboard' => $dashboard->dashboardFor($viewer, $member, $this->period),
        ]);
    }
}
