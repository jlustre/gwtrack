<?php

namespace App\Livewire\Crm;

use App\Models\CrmFunnelParticipation;
use App\Models\CrmPipelineStage;
use App\Services\Crm\CrmFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FunnelBoard extends Component
{
    public string $pipelineKey;

    public function mount(string $pipeline, CrmFunnelService $funnels): void
    {
        abort_unless(auth()->user()?->can('view crm funnels'), 403);
        $this->pipelineKey = $pipeline;
        $funnels->pipelineByKey($pipeline);
    }

    public function moveParticipation(int $participationId, int $stageId, CrmFunnelService $funnels): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $participation = CrmFunnelParticipation::query()->findOrFail($participationId);
        $stage = CrmPipelineStage::query()->findOrFail($stageId);

        abort_unless($participation->crm_pipeline_id === $stage->crm_pipeline_id, 403);

        $funnels->moveToStage($participation, $stage, auth()->user(), 'Moved on board');
    }

    public function render(CrmFunnelService $funnels): View
    {
        $board = $funnels->boardFor($this->pipelineKey);

        return view('livewire.crm.funnel-board', [
            'pipeline' => $board['pipeline'],
            'columns' => $board['columns'],
        ])->layout('layouts.app');
    }
}
