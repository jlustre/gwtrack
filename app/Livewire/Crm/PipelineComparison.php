<?php

namespace App\Livewire\Crm;

use App\Models\CrmPipeline;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class PipelineComparison extends Component
{
    /** @var list<int|null> */
    #[Url(as: 'cols')]
    public array $columnPipelineIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view crm funnels'), 403);

        $pipelineIds = CrmPipeline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($this->columnPipelineIds === []) {
            $this->columnPipelineIds = array_slice($pipelineIds, 0, min(2, count($pipelineIds)));
        }

        $this->columnPipelineIds = collect($this->columnPipelineIds)
            ->map(fn ($id) => $id !== null && $id !== '' ? (int) $id : null)
            ->filter(fn (?int $id) => $id === null || in_array($id, $pipelineIds, true))
            ->values()
            ->all();

        if ($this->columnPipelineIds === [] && $pipelineIds !== []) {
            $this->columnPipelineIds = array_slice($pipelineIds, 0, min(2, count($pipelineIds)));
        }

        while (count($this->columnPipelineIds) < 2 && $pipelineIds !== []) {
            $this->columnPipelineIds[] = $pipelineIds[count($this->columnPipelineIds) % count($pipelineIds)];
        }
    }

    public function setColumnPipeline(int $columnIndex, ?int $pipelineId): void
    {
        if (! array_key_exists($columnIndex, $this->columnPipelineIds)) {
            return;
        }

        $this->columnPipelineIds[$columnIndex] = $pipelineId;
    }

    public function updatedColumnPipelineIds(): void
    {
        $this->columnPipelineIds = collect($this->columnPipelineIds)
            ->map(fn ($id) => $id === '' || $id === null ? null : (int) $id)
            ->values()
            ->all();
    }

    public function addColumn(): void
    {
        if (count($this->columnPipelineIds) >= 4) {
            return;
        }

        $used = collect($this->columnPipelineIds)->filter()->all();
        $next = CrmPipeline::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->first(fn (int $id) => ! in_array($id, $used, true));

        $this->columnPipelineIds[] = $next;
    }

    public function removeColumn(int $columnIndex): void
    {
        if (count($this->columnPipelineIds) <= 2) {
            return;
        }

        unset($this->columnPipelineIds[$columnIndex]);
        $this->columnPipelineIds = array_values($this->columnPipelineIds);
    }

    public function render(): View
    {
        $pipelines = CrmPipeline::query()
            ->where('is_active', true)
            ->with(['stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $columns = collect($this->columnPipelineIds)->map(function (?int $pipelineId) use ($pipelines): array {
            $pipeline = $pipelineId
                ? $pipelines->firstWhere('id', $pipelineId)
                : null;

            return [
                'pipeline_id' => $pipelineId,
                'pipeline' => $pipeline,
                'stages' => $pipeline?->stages ?? collect(),
            ];
        });

        return view('livewire.crm.pipeline-comparison', [
            'pipelines' => $pipelines,
            'columns' => $columns,
            'maxStageRows' => $columns->max(fn (array $column) => $column['stages']->count()) ?: 0,
        ])->layout('layouts.app');
    }
}
