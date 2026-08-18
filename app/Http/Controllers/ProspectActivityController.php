<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Services\Prospects\ProspectFunnelService;
use App\Support\ProspectActivityForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProspectActivityController extends Controller
{
    public function __construct(
        private readonly ProspectFunnelService $funnels,
    ) {}

    public function index(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('viewAny', [ProspectActivity::class, $prospect]);

        $prospect->loadMissing(['stage:id,name', 'funnel:id,name,key']);

        $activities = $prospect->activities()
            ->with('user:id,name')
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (ProspectActivity $activity) => $this->serializeActivity($activity));

        return response()->json([
            'activities' => $activities,
            'pipeline_stages' => $this->pipelineStagesFor($prospect),
            'current_pipeline_stage_id' => $prospect->pipeline_stage_id,
            'current_pipeline_stage_name' => $prospect->stage?->name,
            'activity_types' => ProspectActivityForm::activityTypes(),
            'form_fields' => ProspectActivityForm::apiFieldDefinitions(),
            'outcome_suggestions' => ProspectActivityForm::outcomeSuggestions(),
        ]);
    }

    public function store(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('create', [ProspectActivity::class, $prospect]);

        $validated = $this->validatedApiInput($request);

        $activity = $this->funnels->logActivity(
            $prospect,
            $request->user(),
            ProspectActivityForm::toLogPayloadFromApi($validated),
        );

        return response()->json([
            'activity' => $this->serializeActivity($activity->load('user:id,name')),
            'current_pipeline_stage_id' => $prospect->fresh()->pipeline_stage_id,
            'current_pipeline_stage_name' => $prospect->fresh()->stage?->name,
            'message' => 'Activity logged.',
        ], 201);
    }

    public function update(Request $request, Prospect $prospect, ProspectActivity $activity): JsonResponse
    {
        abort_unless($activity->prospect_id === $prospect->id, 404);

        $this->authorize('update', $activity);

        $validated = $this->validatedApiInput($request);
        $payload = ProspectActivityForm::toLogPayloadFromApi($validated);

        $metadata = is_array($activity->metadata) ? $activity->metadata : [];

        if (filled($payload['subject'] ?? null)) {
            $metadata['subject'] = $payload['subject'];
        } else {
            unset($metadata['subject']);
        }

        $pipelineStageId = $payload['pipeline_stage_id'] ?? null;

        $activity->update([
            'activity_type' => $payload['activity_type'],
            'occurred_at' => $payload['occurred_at'],
            'duration_minutes' => $payload['duration_minutes'] ?? null,
            'outcome' => $payload['outcome'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'next_action' => $payload['next_action'] ?? null,
            'next_follow_up_at' => $payload['next_follow_up_at'] ?? null,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        if (filled($pipelineStageId)) {
            $stageId = (int) $pipelineStageId;

            if ($stageId !== (int) $prospect->pipeline_stage_id) {
                $stageLabel = $this->funnels->stageLabelForFunnel($prospect->prospect_funnel_id, $stageId);
                $activity->update([
                    'metadata' => array_merge($activity->metadata ?? [], [
                        'pipeline_stage_id' => $stageId,
                        'pipeline_stage_name' => $stageLabel,
                    ]),
                ]);

                $this->funnels->updateProspect($prospect, $request->user(), [
                    'pipeline_stage_id' => $stageId,
                ], 'activity_log');
            }
        }

        $this->syncProspectContactFields($prospect->fresh(), $activity->fresh());

        return response()->json([
            'activity' => $this->serializeActivity($activity->load('user:id,name')),
            'current_pipeline_stage_id' => $prospect->fresh()->pipeline_stage_id,
            'current_pipeline_stage_name' => $prospect->fresh()->stage?->name,
            'message' => 'Activity updated.',
        ]);
    }

    public function destroy(Prospect $prospect, ProspectActivity $activity): JsonResponse
    {
        abort_unless($activity->prospect_id === $prospect->id, 404);

        $this->authorize('delete', $activity);

        $activity->delete();

        return response()->json(['message' => 'Activity deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedApiInput(Request $request): array
    {
        $this->normalizeLegacyActivityType($request);

        return $request->validate(ProspectActivityForm::apiValidationRules());
    }

    private function normalizeLegacyActivityType(Request $request): void
    {
        $aliases = [
            'call' => 'phone_call',
            'text' => 'text_message',
            'meeting' => 'zoom_meeting',
            'visit' => 'in_person_meeting',
        ];

        $activityType = (string) $request->input('activity_type', '');

        if (array_key_exists($activityType, $aliases)) {
            $request->merge(['activity_type' => $aliases[$activityType]]);
        }
    }

    private function syncProspectContactFields(Prospect $prospect, ProspectActivity $activity): void
    {
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
    }

    /**
     * @return list<array{id: int, name: string, label: string, sequence: int, slug: string|null}>
     */
    private function pipelineStagesFor(Prospect $prospect): array
    {
        return $this->funnels->numberedStagesForFunnel($prospect->prospect_funnel_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeActivity(ProspectActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'activity_type' => $activity->activity_type,
            'activity_type_label' => ProspectActivityForm::activityTypeLabel($activity->activity_type),
            'subject' => $activity->metadata['subject'] ?? null,
            'notes' => $activity->notes,
            'occurred_at' => $activity->occurred_at?->toIso8601String(),
            'occurred_at_label' => $activity->occurred_at?->format('M j, Y g:i A'),
            'duration_minutes' => $activity->duration_minutes,
            'outcome' => $activity->outcome,
            'next_action' => $activity->next_action,
            'next_follow_up_at' => $activity->next_follow_up_at?->toIso8601String(),
            'next_follow_up_at_label' => $activity->next_follow_up_at?->format('M j, Y g:i A'),
            'pipeline_stage_id' => $activity->metadata['pipeline_stage_id'] ?? null,
            'pipeline_stage_name' => $activity->metadata['pipeline_stage_name'] ?? null,
            'user_name' => $activity->user?->name,
            'can_edit' => auth()->user()?->can('update', $activity) ?? false,
            'can_delete' => auth()->user()?->can('delete', $activity) ?? false,
        ];
    }
}
