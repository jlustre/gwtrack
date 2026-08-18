<?php

namespace App\Livewire\Recruiting;

use App\Models\Prospect;
use App\Models\ProspectFunnel;
use App\Models\ProspectFunnelStage;
use App\Services\Prospects\PipelineStageInfoService;
use App\Services\Prospects\ProspectFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class RecruitingFunnelBoard extends Component
{
    public bool $showStageInfoModal = false;

    /** @var array<string, mixed> */
    public array $stageInfo = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    #[On('recruiting-board-refresh')]
    public function refreshBoard(): void
    {
        // Re-render loads fresh data.
    }

    public function moveProspect(string $prospectId, int $stageId, ProspectFunnelService $funnels): void
    {
        $prospect = Prospect::query()->findOrFail($prospectId);
        $this->authorize('update', $prospect);

        if ((int) $prospect->pipeline_stage_id === $stageId) {
            return;
        }

        $funnels->moveStage($prospect, auth()->user(), $stageId, 'recruiting_kanban');
        $this->dispatch('recruiting-board-refresh');
    }

    public function openStageInfo(int $funnelStageId, PipelineStageInfoService $stageInfo): void
    {
        $funnelKey = config('recruiting-pipeline.funnel_key', 'recruiting');

        $stage = ProspectFunnelStage::query()
            ->whereKey($funnelStageId)
            ->whereHas('funnel', fn ($query) => $query->where('key', $funnelKey))
            ->firstOrFail();

        $this->stageInfo = $stageInfo->forStage($funnelKey, $stage);
        $this->showStageInfoModal = true;
    }

    public function closeStageInfo(): void
    {
        $this->showStageInfoModal = false;
        $this->stageInfo = [];
    }

    public function render(ProspectFunnelService $funnels): View
    {
        $funnel = ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('key', config('recruiting-pipeline.funnel_key', 'recruiting'))
            ->where('is_active', true)
            ->firstOrFail();

        $stages = $funnels->stagesForFunnel($funnel->id);
        $stageIds = $stages->pluck('pipeline_stage_id')->filter()->all();

        $prospects = Prospect::query()
            ->where('owner_id', auth()->id())
            ->where('status', 'active')
            ->where('is_archived', false)
            ->forRecruitingPipeline()
            ->whereIn('pipeline_stage_id', $stageIds)
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('next_follow_up_at')
            ->get(['id', 'first_name', 'last_name', 'preferred_name', 'phone', 'interest_level', 'priority', 'pipeline_stage_id', 'next_follow_up_at']);

        $groupedProspects = $prospects->groupBy(fn (Prospect $prospect): int => (int) $prospect->pipeline_stage_id);

        return view('livewire.recruiting.recruiting-funnel-board', [
            'funnel' => $funnel,
            'stages' => $stages,
            'groupedProspects' => $groupedProspects,
        ]);
    }
}
