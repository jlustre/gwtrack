<?php

namespace App\Livewire\Crm;

use App\Services\Crm\CrmFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FunnelDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view crm funnels'), 403);
    }

    public function render(CrmFunnelService $funnels): View
    {
        $pipelines = $funnels->activePipelines()->map(function ($pipeline) {
            return [
                'pipeline' => $pipeline,
                'active_count' => $pipeline->participations()->where('state', 'active')->count(),
                'stage_count' => $pipeline->stages->count(),
            ];
        });

        return view('livewire.crm.funnel-dashboard', [
            'pipelines' => $pipelines,
        ])->layout('layouts.app');
    }
}
