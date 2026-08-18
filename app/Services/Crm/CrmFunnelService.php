<?php

namespace App\Services\Crm;

use App\Models\CrmFunnelParticipation;
use App\Models\CrmFunnelStageHistory;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CrmFunnelService
{
    /**
     * @return Collection<int, CrmPipeline>
     */
    public function activePipelines(): Collection
    {
        return CrmPipeline::query()
            ->where('is_active', true)
            ->with(['stages' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    public function pipelineByKey(string $key): CrmPipeline
    {
        return CrmPipeline::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->with(['stages' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->firstOrFail();
    }

    /**
     * @return array{
     *     member: User,
     *     pipelines: list<array<string, mixed>>,
     *     active_count: int,
     *     completed_count: int
     * }
     */
    public function consultantOverview(User $member): array
    {
        $pipelines = $this->activePipelines();
        $participations = CrmFunnelParticipation::query()
            ->where('user_id', $member->id)
            ->with(['stage', 'pipeline', 'owner:id,name'])
            ->get()
            ->keyBy('crm_pipeline_id');

        $rows = $pipelines->map(function (CrmPipeline $pipeline) use ($participations): array {
            /** @var CrmFunnelParticipation|null $participation */
            $participation = $participations->get($pipeline->id);
            $stageCount = max(1, $pipeline->stages->count());
            $stageIndex = $participation
                ? max(0, $pipeline->stages->search(fn (CrmPipelineStage $stage) => $stage->id === $participation->crm_pipeline_stage_id))
                : null;

            $progress = $participation?->progress_percent
                ?? ($stageIndex === null ? 0 : (int) round((($stageIndex + 1) / $stageCount) * 100));

            return [
                'pipeline_id' => $pipeline->id,
                'key' => $pipeline->key,
                'name' => $pipeline->name,
                'short_name' => $pipeline->short_name ?: $pipeline->name,
                'color' => $pipeline->color,
                'description' => $pipeline->description,
                'enrolled' => $participation !== null,
                'stage' => $participation?->stage?->name,
                'stage_id' => $participation?->crm_pipeline_stage_id,
                'status' => $participation?->status ?? 'not_started',
                'state' => $participation?->state ?? 'not_started',
                'progress_percent' => $progress,
                'owner' => $participation?->owner?->name,
                'entered_at' => $participation?->entered_at?->format('M j, Y'),
                'stage_entered_at' => $participation?->stage_entered_at?->format('M j, Y'),
                'next_action' => $participation?->next_action,
                'indicator' => $this->statusIndicator($participation),
            ];
        })->values()->all();

        return [
            'member' => $member,
            'pipelines' => $rows,
            'active_count' => collect($rows)->where('state', 'active')->count(),
            'completed_count' => collect($rows)->whereIn('state', ['completed', 'closed'])->count(),
        ];
    }

    /**
     * @return array{
     *     pipeline: CrmPipeline,
     *     columns: list<array{stage: CrmPipelineStage, participations: Collection<int, CrmFunnelParticipation>}>
     * }
     */
    public function boardFor(string $pipelineKey): array
    {
        $pipeline = $this->pipelineByKey($pipelineKey);
        $participations = CrmFunnelParticipation::query()
            ->where('crm_pipeline_id', $pipeline->id)
            ->whereIn('state', ['active', 'paused'])
            ->with(['user:id,name,email', 'owner:id,name', 'stage'])
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('crm_pipeline_stage_id');

        $columns = $pipeline->stages->map(fn (CrmPipelineStage $stage): array => [
            'stage' => $stage,
            'participations' => $participations->get($stage->id, collect()),
        ])->values()->all();

        return [
            'pipeline' => $pipeline,
            'columns' => $columns,
        ];
    }

    public function enroll(
        User $member,
        CrmPipeline $pipeline,
        ?CrmPipelineStage $stage = null,
        ?User $owner = null,
        ?User $actor = null,
    ): CrmFunnelParticipation {
        $stage ??= $pipeline->stages()->where('is_active', true)->orderBy('sort_order')->first();

        if (! $stage) {
            throw new InvalidArgumentException('This pipeline has no active stages.');
        }

        return DB::transaction(function () use ($member, $pipeline, $stage, $owner, $actor): CrmFunnelParticipation {
            $participation = CrmFunnelParticipation::query()->updateOrCreate(
                [
                    'user_id' => $member->id,
                    'crm_pipeline_id' => $pipeline->id,
                ],
                [
                    'crm_pipeline_stage_id' => $stage->id,
                    'owner_id' => $owner?->id ?? $actor?->id,
                    'status' => 'active',
                    'state' => 'active',
                    'progress_percent' => $this->progressForStage($pipeline, $stage),
                    'entered_at' => now()->toDateString(),
                    'stage_entered_at' => now()->toDateString(),
                    'last_activity_at' => now(),
                ],
            );

            CrmFunnelStageHistory::query()->create([
                'crm_funnel_participation_id' => $participation->id,
                'from_stage_id' => null,
                'to_stage_id' => $stage->id,
                'changed_by' => $actor?->id,
                'reason' => 'Enrolled in pipeline',
            ]);

            return $participation->load(['stage', 'pipeline', 'owner']);
        });
    }

    public function moveToStage(
        CrmFunnelParticipation $participation,
        CrmPipelineStage $stage,
        ?User $actor = null,
        ?string $reason = null,
    ): CrmFunnelParticipation {
        if ($stage->crm_pipeline_id !== $participation->crm_pipeline_id) {
            throw new InvalidArgumentException('Stage does not belong to this pipeline.');
        }

        return DB::transaction(function () use ($participation, $stage, $actor, $reason): CrmFunnelParticipation {
            $fromStageId = $participation->crm_pipeline_stage_id;

            $participation->forceFill([
                'crm_pipeline_stage_id' => $stage->id,
                'stage_entered_at' => now()->toDateString(),
                'progress_percent' => $this->progressForStage($participation->pipeline, $stage),
                'last_activity_at' => now(),
                'state' => $stage->is_terminal ? 'completed' : $participation->state,
                'completed_at' => $stage->is_terminal ? now()->toDateString() : $participation->completed_at,
            ])->save();

            CrmFunnelStageHistory::query()->create([
                'crm_funnel_participation_id' => $participation->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $stage->id,
                'changed_by' => $actor?->id,
                'reason' => $reason,
            ]);

            return $participation->fresh(['stage', 'pipeline', 'owner', 'user']);
        });
    }

    public function progressForStage(CrmPipeline $pipeline, CrmPipelineStage $stage): int
    {
        $stages = $pipeline->relationLoaded('stages')
            ? $pipeline->stages->where('is_active', true)->values()
            : $pipeline->stages()->where('is_active', true)->orderBy('sort_order')->get();

        $count = max(1, $stages->count());
        $index = $stages->search(fn (CrmPipelineStage $item) => $item->id === $stage->id);

        if ($index === false) {
            return 0;
        }

        return (int) min(100, round((($index + 1) / $count) * 100));
    }

    public function makeStageSlug(string $name): string
    {
        return Str::slug($name);
    }

    private function statusIndicator(?CrmFunnelParticipation $participation): string
    {
        if ($participation === null) {
            return 'gray';
        }

        return match ($participation->state) {
            'completed', 'closed' => 'green',
            'paused' => 'orange',
            'active' => $participation->status === 'attention' ? 'yellow' : 'blue',
            default => 'gray',
        };
    }
}
