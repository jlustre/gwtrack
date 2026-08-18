<?php

namespace App\Livewire\Admin\Crm;

use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Services\Crm\CrmFunnelConfigExporter;
use App\Services\Crm\CrmFunnelService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PipelineManager extends Component
{
    public ?int $selectedPipelineId = null;

    public string $pipelineName = '';

    public string $pipelineKey = '';

    public string $pipelineShortName = '';

    public string $pipelineDescription = '';

    public string $pipelineColor = '#2D3E50';

    public string $stageName = '';

    public bool $stageIsTerminal = false;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $this->selectedPipelineId = CrmPipeline::query()->orderBy('sort_order')->value('id');
    }

    public function selectPipeline(int $pipelineId): void
    {
        $this->selectedPipelineId = $pipelineId;
        $this->resetStageForm();
    }

    public function updateSeeder(CrmFunnelConfigExporter $exporter): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);
        abort_unless(auth()->user()?->hasAnyRole(['super-admin', 'admin']), 403);

        $exporter->export();
        $this->statusMessage = 'seeder-updated';
    }

    public function savePipeline(): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $validated = $this->validate([
            'pipelineName' => ['required', 'string', 'max:120'],
            'pipelineKey' => ['required', 'string', 'max:80', 'alpha_dash'],
            'pipelineShortName' => ['nullable', 'string', 'max:40'],
            'pipelineDescription' => ['nullable', 'string', 'max:1000'],
            'pipelineColor' => ['required', 'string', 'max:20'],
        ]);

        $maxOrder = (int) CrmPipeline::query()->max('sort_order');

        CrmPipeline::query()->updateOrCreate(
            ['key' => strtolower($validated['pipelineKey'])],
            [
                'name' => $validated['pipelineName'],
                'short_name' => $validated['pipelineShortName'] ?: null,
                'description' => $validated['pipelineDescription'] ?: null,
                'color' => $validated['pipelineColor'],
                'sort_order' => $maxOrder + 10,
                'is_active' => true,
            ],
        );

        $this->reset(['pipelineName', 'pipelineKey', 'pipelineShortName', 'pipelineDescription']);
        $this->pipelineColor = '#2D3E50';
        $this->statusMessage = 'pipeline-saved';
    }

    public function togglePipeline(int $pipelineId): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $pipeline = CrmPipeline::query()->findOrFail($pipelineId);
        $pipeline->forceFill(['is_active' => ! $pipeline->is_active])->save();
    }

    public function addStage(CrmFunnelService $funnels): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);
        abort_unless($this->selectedPipelineId, 422);

        $validated = $this->validate([
            'stageName' => ['required', 'string', 'max:160'],
            'stageIsTerminal' => ['boolean'],
        ]);

        $pipeline = CrmPipeline::query()->findOrFail($this->selectedPipelineId);
        $maxOrder = (int) $pipeline->stages()->max('sort_order');

        CrmPipelineStage::query()->updateOrCreate(
            [
                'crm_pipeline_id' => $pipeline->id,
                'slug' => $funnels->makeStageSlug($validated['stageName']),
            ],
            [
                'name' => $validated['stageName'],
                'color' => $pipeline->color,
                'sort_order' => $maxOrder + 10,
                'is_terminal' => $validated['stageIsTerminal'],
                'is_active' => true,
            ],
        );

        $this->resetStageForm();
        $this->statusMessage = 'stage-saved';
    }

    public function toggleStage(int $stageId): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $stage = CrmPipelineStage::query()->findOrFail($stageId);
        $stage->forceFill(['is_active' => ! $stage->is_active])->save();
    }

    public function deleteStage(int $stageId): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $stage = CrmPipelineStage::query()->findOrFail($stageId);

        abort_unless(
            (int) $stage->crm_pipeline_id === (int) $this->selectedPipelineId,
            404,
        );

        $pipelineId = (int) $stage->crm_pipeline_id;
        $stage->delete();
        $this->renumberPipelineStages($pipelineId);
        $this->statusMessage = 'stage-deleted';
    }

    public function moveStage(int $stageId, string $direction): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);

        $stage = CrmPipelineStage::query()->findOrFail($stageId);
        $neighbor = CrmPipelineStage::query()
            ->where('crm_pipeline_id', $stage->crm_pipeline_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('sort_order', '<', $stage->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $stage->sort_order)->orderBy('sort_order'),
            )
            ->first();

        if (! $neighbor) {
            return;
        }

        $currentOrder = $stage->sort_order;
        $stage->forceFill(['sort_order' => $neighbor->sort_order])->save();
        $neighbor->forceFill(['sort_order' => $currentOrder])->save();
    }

    public function render(): View
    {
        $pipelines = CrmPipeline::query()->orderBy('sort_order')->get();
        $selected = $this->selectedPipelineId
            ? CrmPipeline::query()
                ->with(['stages' => fn ($q) => $q->orderBy('sort_order')])
                ->find($this->selectedPipelineId)
            : null;

        return view('livewire.admin.crm.pipeline-manager', [
            'pipelines' => $pipelines,
            'selected' => $selected,
            'canUpdateSeeder' => auth()->user()?->hasAnyRole(['super-admin', 'admin']) ?? false,
        ])->layout('layouts.app');
    }

    private function resetStageForm(): void
    {
        $this->stageName = '';
        $this->stageIsTerminal = false;
        $this->resetValidation();
    }

    private function renumberPipelineStages(int $pipelineId): void
    {
        CrmPipelineStage::query()
            ->where('crm_pipeline_id', $pipelineId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (CrmPipelineStage $stage, int $index): void {
                $nextOrder = ($index + 1) * 10;

                if ((int) $stage->sort_order === $nextOrder) {
                    return;
                }

                $stage->forceFill(['sort_order' => $nextOrder])->save();
            });
    }
}
