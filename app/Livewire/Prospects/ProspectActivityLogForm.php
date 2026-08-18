<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Services\Prospects\ProspectFunnelService;
use App\Support\ProspectActivityForm;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectActivityLogForm extends Component
{
    public ?string $prospectId = null;

    public ?int $activityId = null;

    /** @var array<string, mixed> */
    public array $state = [];

    public bool $embedded = false;

    public function mount(?string $prospectId = null, ?string $initialActivityType = null, bool $embedded = false, ?int $activityId = null): void
    {
        $this->prospectId = $prospectId;
        $this->embedded = $embedded;
        $this->activityId = $activityId;

        if ($activityId) {
            $this->loadActivity($activityId);
        } else {
            $this->resetForm($initialActivityType);
        }
    }

    public function setQuickActivity(string $type): void
    {
        if (! array_key_exists($type, ProspectActivityForm::activityTypes())) {
            return;
        }

        $this->state['activity_type'] = $type;
    }

    public function save(ProspectFunnelService $funnels): void
    {
        $prospect = $this->prospect();

        $validated = $this->validate(ProspectActivityForm::livewireValidationRules());
        $payload = ProspectActivityForm::toLogPayload($validated['state']);

        if ($this->activityId) {
            $activity = ProspectActivity::query()
                ->whereKey($this->activityId)
                ->where('prospect_id', $prospect->id)
                ->firstOrFail();

            $this->authorize('update', $activity);

            $funnels->updateActivity($prospect, auth()->user(), $activity, $payload);
        } else {
            $funnels->logActivity($prospect, auth()->user(), $payload);
        }

        $this->dispatch('prospect-activity-logged');
        $this->dispatch('prospect-timeline-refresh');
        $this->dispatch('prospect-board-refresh');

        if ($this->activityId) {
            $this->loadActivity($this->activityId);
        } else {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        if ($this->activityId) {
            $this->loadActivity($this->activityId);
        } else {
            $this->resetForm();
        }

        $this->dispatch('cancel-prospect-activity-log-form');
    }

    public function render(ProspectFunnelService $funnels): View
    {
        $stages = collect();
        $prospect = $this->prospectId
            ? Prospect::query()->find($this->prospectId)
            : null;

        if ($prospect) {
            $stages = collect($funnels->numberedStagesForFunnel($prospect->prospect_funnel_id));
        }

        $resolvedFields = collect(ProspectActivityForm::fieldDefinitions())
            ->map(function (array $field) use ($stages): array {
                $optionsKey = $field['options_key'] ?? null;

                return array_merge($field, [
                    'options' => is_string($optionsKey)
                        ? ProspectActivityForm::optionsFor($optionsKey, $stages->all())
                        : [],
                    'suggestions' => isset($field['suggestions_key'])
                        ? ProspectActivityForm::outcomeSuggestions()
                        : [],
                ]);
            })
            ->values()
            ->all();

        $activityType = (string) ($this->state['activity_type'] ?? 'phone_call');

        return view('livewire.prospects.prospect-activity-log-form', [
            'resolvedFields' => $resolvedFields,
            'quickActivityTypes' => ProspectActivityForm::quickActivityTypes(),
            'activityTypeLabel' => ProspectActivityForm::activityTypeLabel($activityType),
            'isEditing' => filled($this->activityId),
        ]);
    }

    private function loadActivity(int $activityId): void
    {
        $activity = ProspectActivity::query()
            ->whereKey($activityId)
            ->where('prospect_id', $this->prospectId)
            ->firstOrFail();

        $this->authorize('update', $activity);
        $this->activityId = $activity->id;
        $this->state = ProspectActivityForm::stateFromActivity($activity);
        $this->resetValidation();
    }

    private function prospect(): Prospect
    {
        $prospect = Prospect::query()->findOrFail($this->prospectId);
        $this->authorize('update', $prospect);

        return $prospect;
    }

    private function resetForm(?string $activityType = null): void
    {
        $this->activityId = null;

        $prospect = $this->prospectId
            ? Prospect::query()->find($this->prospectId)
            : null;

        $this->state = ProspectActivityForm::defaultState(
            $prospect?->pipeline_stage_id,
            $activityType,
        );

        $this->resetValidation();
    }
}
