<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectFunnel;
use App\Models\ProspectFunnelStage;
use App\Services\Prospects\PipelineStageInfoService;
use App\Services\Prospects\ProspectFunnelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ProspectFunnelBoard extends Component
{
    private const FUNNEL_KEY = 'insurance';

    public bool $showStageInfoModal = false;

    /** @var array<string, mixed> */
    public array $stageInfo = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    #[On('prospect-board-refresh')]
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

        $funnels->moveStage($prospect, auth()->user(), $stageId, 'kanban');
    }

    public function openStageInfo(int $funnelStageId, PipelineStageInfoService $stageInfo): void
    {
        $stage = ProspectFunnelStage::query()
            ->whereKey($funnelStageId)
            ->whereHas('funnel', fn ($query) => $query->where('key', self::FUNNEL_KEY))
            ->firstOrFail();

        $this->stageInfo = $stageInfo->forStage(self::FUNNEL_KEY, $stage);
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
            ->where('key', self::FUNNEL_KEY)
            ->where('is_active', true)
            ->firstOrFail();

        $stages = $funnels->stagesForFunnel($funnel->id);

        $stageIds = $stages->pluck('pipeline_stage_id')->filter()->all();

        $prospects = Prospect::query()
            ->where('owner_id', auth()->id())
            ->where('status', 'active')
            ->where('is_archived', false)
            ->forSalesPipeline()
            ->whereIn('pipeline_stage_id', $stageIds)
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('next_follow_up_at')
            ->get(['id', 'first_name', 'last_name', 'preferred_name', 'phone', 'interest_level', 'priority', 'pipeline_stage_id', 'next_follow_up_at']);

        /** @var Collection<int|string, Collection<int, Prospect>> $grouped */
        $grouped = $prospects->groupBy(fn (Prospect $prospect): int => (int) $prospect->pipeline_stage_id);

        return view('livewire.prospects.prospect-funnel-board', [
            'funnel' => $funnel,
            'stages' => $stages,
            'groupedProspects' => $grouped,
        ]);
    }
}
