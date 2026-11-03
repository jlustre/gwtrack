<?php

namespace App\Services\Prospects;

use App\Events\Prospects\ProspectStageChanged;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ProspectCommunication;
use App\Models\ProspectFunnel;
use App\Models\ProspectFunnelStage;
use App\Models\ProspectNote;
use App\Models\ProspectStageHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProspectFunnelService
{
    public function __construct(
        private readonly CrmAutomationService $automations,
        private readonly ProspectTimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createProspect(User $owner, array $attributes): Prospect
    {
        return DB::transaction(function () use ($owner, $attributes): Prospect {
            $funnel = $this->resolveFunnel($attributes['funnel_type'] ?? 'insurance', $attributes['prospect_funnel_id'] ?? null);
            $stageId = $attributes['pipeline_stage_id'] ?? $this->defaultStageIdForFunnel($funnel);
            $this->assertStageBelongsToFunnel($funnel->id, $stageId);

            $prospect = Prospect::create([
                ...$attributes,
                'owner_id' => $owner->id,
                'prospect_funnel_id' => $funnel->id,
                'pipeline_stage_id' => $stageId,
                'last_activity_at' => now(),
            ]);

            if ($stageId) {
                $this->recordStageHistory($prospect, null, $stageId, $owner, 'create');
            }

            $prospect = $prospect->fresh(['source', 'stage', 'funnel']);
            app(CrmRelationshipExpansionService::class)->syncProspect($prospect);

            return $prospect->fresh(['source', 'stage', 'funnel']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProspect(Prospect $prospect, User $actor, array $attributes, string $stageChangeSource = 'manual'): Prospect
    {
        return DB::transaction(function () use ($prospect, $actor, $attributes, $stageChangeSource): Prospect {
            $previousStageId = $prospect->pipeline_stage_id;
            $previousFunnelId = $prospect->prospect_funnel_id;
            $nextFunnelId = $previousFunnelId;

            if (isset($attributes['funnel_type']) || isset($attributes['prospect_funnel_id'])) {
                try {
                    $funnel = $this->resolveFunnel(
                        $attributes['funnel_type'] ?? $prospect->funnel_type,
                        $attributes['prospect_funnel_id'] ?? $prospect->prospect_funnel_id,
                    );
                    $attributes['prospect_funnel_id'] = $funnel->id;
                    $nextFunnelId = $funnel->id;
                } catch (ModelNotFoundException) {
                    unset($attributes['prospect_funnel_id']);
                }
            }

            if ($nextFunnelId !== $previousFunnelId && ! array_key_exists('pipeline_stage_id', $attributes)) {
                $attributes['pipeline_stage_id'] = $this->defaultStageIdForFunnel(
                    ProspectFunnel::query()->findOrFail($nextFunnelId),
                );
            }
            $this->assertStageBelongsToFunnel(
                $nextFunnelId,
                $attributes['pipeline_stage_id'] ?? $previousStageId,
            );

            $prospect->update([
                ...$attributes,
                'last_activity_at' => now(),
            ]);

            if (
                array_key_exists('pipeline_stage_id', $attributes)
                && (int) $attributes['pipeline_stage_id'] !== (int) $previousStageId
            ) {
                $this->recordStageHistory(
                    $prospect,
                    $previousStageId,
                    $prospect->pipeline_stage_id,
                    $actor,
                    $stageChangeSource,
                    $previousFunnelId,
                    $prospect->prospect_funnel_id,
                );

                event(new ProspectStageChanged(
                    $prospect->fresh(['stage']),
                    $actor,
                    $previousStageId,
                    (int) $prospect->pipeline_stage_id,
                    $stageChangeSource,
                ));
            }

            $prospect = $prospect->fresh(['source', 'stage', 'funnel']);
            app(CrmRelationshipExpansionService::class)->syncProspect($prospect);

            return $prospect->fresh(['source', 'stage', 'funnel']);
        });
    }

    public function syncRelationships(Prospect $prospect): void
    {
        app(CrmRelationshipExpansionService::class)->syncProspect($prospect->fresh());
    }

    public function moveStage(Prospect $prospect, User $actor, int $stageId, string $source = 'manual'): Prospect
    {
        return $this->updateProspect($prospect, $actor, ['pipeline_stage_id' => $stageId], $source);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logActivity(Prospect $prospect, User $actor, array $attributes): ProspectActivity
    {
        $metadata = is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [];

        if (filled($attributes['subject'] ?? null)) {
            $metadata['subject'] = $attributes['subject'];
        }

        $activity = ProspectActivity::create([
            'prospect_id' => $prospect->id,
            'user_id' => $actor->id,
            'activity_type' => $attributes['activity_type'],
            'occurred_at' => $attributes['occurred_at'] ?? now(),
            'duration_minutes' => $attributes['duration_minutes'] ?? null,
            'outcome' => $attributes['outcome'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'next_action' => $attributes['next_action'] ?? null,
            'next_follow_up_at' => $attributes['next_follow_up_at'] ?? null,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        $prospect->update([
            'last_contacted_at' => $activity->occurred_at,
            'last_activity_at' => now(),
            'next_follow_up_at' => $activity->next_follow_up_at ?? $prospect->next_follow_up_at,
        ]);

        if (filled($attributes['pipeline_stage_id'] ?? null)) {
            $stageId = (int) $attributes['pipeline_stage_id'];

            if ($stageId !== (int) $prospect->pipeline_stage_id) {
                $stageLabel = $this->stageLabelForFunnel($prospect->prospect_funnel_id, $stageId);
                $activity->update([
                    'metadata' => array_merge($activity->metadata ?? [], [
                        'pipeline_stage_id' => $stageId,
                        'pipeline_stage_name' => $stageLabel,
                    ]),
                ]);

                $this->updateProspect($prospect->fresh(), $actor, [
                    'pipeline_stage_id' => $stageId,
                ], 'activity_log');
            }
        }

        $this->automations->handleActivityLogged($activity->fresh(['prospect', 'user']));

        return $activity->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateActivity(Prospect $prospect, User $actor, ProspectActivity $activity, array $attributes): ProspectActivity
    {
        abort_unless($activity->prospect_id === $prospect->id, 404);

        $metadata = is_array($activity->metadata) ? $activity->metadata : [];

        if (filled($attributes['subject'] ?? null)) {
            $metadata['subject'] = $attributes['subject'];
        } else {
            unset($metadata['subject']);
        }

        $activity->update([
            'activity_type' => $attributes['activity_type'],
            'occurred_at' => $attributes['occurred_at'] ?? $activity->occurred_at,
            'duration_minutes' => $attributes['duration_minutes'] ?? null,
            'outcome' => $attributes['outcome'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'next_action' => $attributes['next_action'] ?? null,
            'next_follow_up_at' => $attributes['next_follow_up_at'] ?? null,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        if (filled($attributes['pipeline_stage_id'] ?? null)) {
            $stageId = (int) $attributes['pipeline_stage_id'];

            if ($stageId !== (int) $prospect->pipeline_stage_id) {
                $stageLabel = $this->stageLabelForFunnel($prospect->prospect_funnel_id, $stageId);
                $activity->update([
                    'metadata' => array_merge($activity->metadata ?? [], [
                        'pipeline_stage_id' => $stageId,
                        'pipeline_stage_name' => $stageLabel,
                    ]),
                ]);

                $this->updateProspect($prospect->fresh(), $actor, [
                    'pipeline_stage_id' => $stageId,
                ], 'activity_log');
            }
        }

        $prospect->refresh();

        $updates = [];

        if ($activity->occurred_at && (
            ! $prospect->last_contacted_at
            || $activity->occurred_at->greaterThan($prospect->last_contacted_at)
        )) {
            $updates['last_contacted_at'] = $activity->occurred_at;
        }

        if ($activity->next_follow_up_at) {
            $updates['next_follow_up_at'] = $activity->next_follow_up_at;
        }

        if ($activity->occurred_at) {
            $updates['last_activity_at'] = $activity->occurred_at;
        }

        if ($updates !== []) {
            $prospect->update($updates);
        }

        return $activity->fresh(['user']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logCommunication(Prospect $prospect, User $actor, array $attributes): ProspectCommunication
    {
        $communication = ProspectCommunication::create([
            'prospect_id' => $prospect->id,
            'user_id' => $actor->id,
            'communication_type_id' => $attributes['communication_type_id'] ?? null,
            'direction' => $attributes['direction'] ?? 'outbound',
            'contacted_at' => $attributes['contacted_at'] ?? now(),
            'outcome' => $attributes['outcome'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'next_action' => $attributes['next_action'] ?? null,
            'next_follow_up_at' => $attributes['next_follow_up_at'] ?? null,
            'duration_minutes' => $attributes['duration_minutes'] ?? null,
        ]);

        $prospect->update([
            'last_contacted_at' => $communication->contacted_at,
            'last_activity_at' => now(),
            'next_follow_up_at' => $communication->next_follow_up_at ?? $prospect->next_follow_up_at,
        ]);

        $this->automations->handleCommunicationLogged($communication->fresh(['prospect', 'user']));

        return $communication;
    }

    public function addNote(Prospect $prospect, User $actor, string $note, bool $isPrivate = false): ProspectNote
    {
        $record = ProspectNote::create([
            'prospect_id' => $prospect->id,
            'user_id' => $actor->id,
            'note' => $note,
            'is_private' => $isPrivate,
        ]);

        $prospect->update(['last_activity_at' => now()]);

        return $record;
    }

    /**
     * @return list<array{type: string, label: string, body: string, actor: string, occurred_at: string}>
     */
    public function timelineFor(Prospect $prospect): array
    {
        return $this->timeline->forProspect($prospect);
    }

    public function funnelsForSelect(): Collection
    {
        return ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function stagesForFunnel(?int $funnelId): Collection
    {
        if (! $funnelId) {
            return PipelineStage::query()->whereNull('user_id')->where('is_active', true)->orderBy('sort_order')->get();
        }

        return ProspectFunnelStage::query()
            ->where('prospect_funnel_id', $funnelId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return list<array{id: int, name: string, label: string, sequence: int, slug: string|null}>
     */
    public function numberedStagesForFunnel(?int $funnelId): array
    {
        return $this->stagesForFunnel($funnelId)
            ->values()
            ->map(function ($stage, int $index): array {
                $sequence = $index + 1;
                $name = (string) $stage->name;

                return [
                    'id' => (int) ($stage->pipeline_stage_id ?? $stage->id),
                    'name' => $name,
                    'label' => "{$sequence}. {$name}",
                    'sequence' => $sequence,
                    'slug' => $stage->slug ?? null,
                ];
            })
            ->all();
    }

    public function stageLabelForFunnel(?int $funnelId, int $stageId): ?string
    {
        foreach ($this->numberedStagesForFunnel($funnelId) as $stage) {
            if ($stage['id'] === $stageId) {
                return $stage['label'];
            }
        }

        return PipelineStage::query()->whereKey($stageId)->value('name');
    }

    public function primaryFunnelIdFor(User $user): int
    {
        $funnelId = Prospect::query()
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->whereNotNull('prospect_funnel_id')
            ->select('prospect_funnel_id', DB::raw('COUNT(*) as total'))
            ->groupBy('prospect_funnel_id')
            ->orderByDesc('total')
            ->value('prospect_funnel_id');

        if ($funnelId) {
            return (int) $funnelId;
        }

        $defaultFunnelId = ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');

        if ($defaultFunnelId) {
            return (int) $defaultFunnelId;
        }

        return (int) ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('key', 'insurance')
            ->value('id');
    }

    /**
     * @return Collection<int, object{id: int, name: string, label: string, sequence: int, slug: string|null, prospect_count: int}>
     */
    public function pipelineSummaryFor(User $user, ?int $funnelId = null): Collection
    {
        $funnelId = $funnelId ?? $this->primaryFunnelIdFor($user);

        $counts = Prospect::query()
            ->where('owner_id', $user->id)
            ->where('prospect_funnel_id', $funnelId)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->groupBy('pipeline_stage_id')
            ->select('pipeline_stage_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'pipeline_stage_id');

        return collect($this->numberedStagesForFunnel($funnelId))
            ->map(fn (array $stage) => (object) [
                ...$stage,
                'prospect_count' => (int) ($counts[$stage['id']] ?? 0),
            ]);
    }

    /**
     * @return Collection<int, array{key: string, label: string, color: string, stages: Collection<int, object>}>
     */
    public function pipelineSummaryGroupedFor(User $user, ?int $funnelId = null): Collection
    {
        $funnelId = $funnelId ?? $this->primaryFunnelIdFor($user);
        $funnelKey = ProspectFunnel::query()->whereKey($funnelId)->value('key') ?? 'insurance';
        $summary = $this->pipelineSummaryFor($user, $funnelId);
        $groupDefinitions = config("crm-funnels.pipeline_summary_groups.{$funnelKey}", []);

        $slugToGroupKey = [];
        foreach ($groupDefinitions as $definition) {
            foreach ($definition['slugs'] as $slug) {
                $slugToGroupKey[$slug] = $definition['key'];
            }
        }

        $groups = collect($groupDefinitions)->mapWithKeys(function (array $definition): array {
            return [
                $definition['key'] => [
                    'key' => $definition['key'],
                    'label' => $definition['label'],
                    'color' => $definition['color'] ?? 'slate',
                    'stages' => collect(),
                ],
            ];
        });

        $otherStages = collect();

        foreach ($summary as $stage) {
            $groupKey = $slugToGroupKey[$stage->slug ?? ''] ?? null;

            if ($groupKey && $groups->has($groupKey)) {
                $group = $groups->get($groupKey);
                $group['stages']->push($stage);
                $groups->put($groupKey, $group);
            } else {
                $otherStages->push($stage);
            }
        }

        $result = $groups
            ->values()
            ->filter(fn (array $group): bool => $group['stages']->isNotEmpty());

        if ($otherStages->isNotEmpty()) {
            $result->push([
                'key' => 'other',
                'label' => 'Other Stages',
                'color' => 'slate',
                'stages' => $otherStages,
            ]);
        }

        return $result;
    }

    public function resolveFunnel(string $funnelType, ?int $funnelId = null): ProspectFunnel
    {
        if ($funnelId) {
            return ProspectFunnel::query()->findOrFail($funnelId);
        }

        $key = match ($funnelType) {
            'recruiting' => 'recruiting',
            'both' => 'insurance',
            default => 'insurance',
        };

        return ProspectFunnel::query()
            ->whereNull('user_id')
            ->where('key', $key)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function defaultStageIdForFunnel(ProspectFunnel $funnel): ?int
    {
        $stage = ProspectFunnelStage::query()
            ->where('prospect_funnel_id', $funnel->id)
            ->orderBy('sort_order')
            ->first();

        return $stage?->pipeline_stage_id;
    }

    private function assertStageBelongsToFunnel(?int $funnelId, ?int $stageId): void
    {
        if ($stageId === null) {
            return;
        }

        $activeGlobalStage = PipelineStage::query()
            ->whereKey($stageId)
            ->whereNull('user_id')
            ->where('is_active', true)
            ->exists();
        $linkedFunnelIds = ProspectFunnelStage::query()
            ->where('pipeline_stage_id', $stageId)
            ->pluck('prospect_funnel_id');
        $belongsToFunnel = $activeGlobalStage
            && ($funnelId === null
                || $linkedFunnelIds->isEmpty()
                || $linkedFunnelIds->contains($funnelId));

        if (! $belongsToFunnel) {
            throw ValidationException::withMessages([
                'pipeline_stage_id' => 'Select a stage that belongs to this prospect funnel.',
            ]);
        }
    }

    private function recordStageHistory(
        Prospect $prospect,
        ?int $fromStageId,
        ?int $toStageId,
        User $actor,
        string $source,
        ?int $fromFunnelId = null,
        ?int $toFunnelId = null,
    ): void {
        ProspectStageHistory::create([
            'prospect_id' => $prospect->id,
            'from_stage_id' => $fromStageId,
            'to_stage_id' => $toStageId,
            'from_funnel_id' => $fromFunnelId ?? $prospect->prospect_funnel_id,
            'to_funnel_id' => $toFunnelId ?? $prospect->prospect_funnel_id,
            'changed_by' => $actor->id,
            'change_source' => $source,
        ]);
    }
}
