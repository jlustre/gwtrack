<?php

namespace App\Livewire\Prospects;

use App\Models\AppointmentType;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\User;
use App\Services\Prospects\ProspectScheduleActivityService;
use App\Services\Prospects\ScheduleActivityOptionService;
use App\Support\ProspectScheduledActivityForm;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProspectScheduleActivityForm extends Component
{
    public ?string $prospectId = null;

    public ?int $appointmentId = null;

    /** @var array<string, mixed> */
    public array $state = [];

    public bool $embedded = false;

    public function mount(?string $prospectId = null, bool $embedded = false, ?int $appointmentId = null): void
    {
        $this->prospectId = $prospectId;
        $this->embedded = $embedded;
        $this->appointmentId = $appointmentId;

        if ($appointmentId) {
            $this->loadAppointment($appointmentId);
        } else {
            $this->resetForm();
        }
    }

    public function setQuickSchedule(string $slug): void
    {
        $typeId = ProspectScheduledActivityForm::appointmentTypeIdForSlug(
            $slug,
            $this->appointmentTypeOptions(),
        );

        if ($typeId) {
            $this->state['appointment_type_id'] = $typeId;
        }
    }

    public function save(
        ProspectScheduleActivityService $scheduler,
        ScheduleActivityOptionService $optionService,
    ): void {
        $prospect = $this->prospect();

        $validated = $this->validate(ProspectScheduledActivityForm::livewireValidationRules());

        foreach (ProspectScheduledActivityForm::newlyCreatedOptions($validated['state']) as $created) {
            $optionService->remember(auth()->user(), $created['category'], $created['value']);
        }

        $resolvedState = ProspectScheduledActivityForm::resolveCreatableState($validated['state']);
        $payload = ProspectScheduledActivityForm::toSchedulePayload($resolvedState);

        if ($this->appointmentId) {
            $appointment = ProspectAppointment::query()
                ->whereKey($this->appointmentId)
                ->where('prospect_id', $prospect->id)
                ->firstOrFail();

            $this->authorize('update', $appointment);

            $scheduler->update($prospect, auth()->user(), $appointment, $payload);
        } else {
            $scheduler->schedule($prospect, auth()->user(), $payload);
        }

        $this->dispatch('prospect-scheduled-activity-saved');
        $this->dispatch('prospect-timeline-refresh');
        $this->dispatch('prospect-board-refresh');

        if ($this->appointmentId) {
            $this->loadAppointment($this->appointmentId);
        } else {
            $this->resetForm();
        }
    }

    public function cancel(): void
    {
        if ($this->appointmentId) {
            $this->loadAppointment($this->appointmentId);
        } else {
            $this->resetForm();
        }

        $this->dispatch('cancel-prospect-schedule-activity-form');
    }

    public function render(): View
    {
        $appointmentTypes = $this->appointmentTypeOptions();
        $typeId = filled($this->state['appointment_type_id'] ?? null)
            ? (int) $this->state['appointment_type_id']
            : null;

        $user = auth()->user();
        $optionService = app(ScheduleActivityOptionService::class);
        $purposeOptions = $user ? $optionService->optionsFor($user, 'purpose') : [];
        $locationOptions = $user ? $optionService->optionsFor($user, 'location') : [];

        $fieldDefinitions = collect(ProspectScheduledActivityForm::fieldDefinitions())
            ->when($this->appointmentId, fn ($fields) => $fields->reject(
                fn (array $field): bool => ($field['key'] ?? '') === 'create_prep_task',
            ))
            ->values();

        $resolvedFields = $fieldDefinitions
            ->map(function (array $field) use ($appointmentTypes, $purposeOptions, $locationOptions): array {
                $optionsKey = $field['options_key'] ?? null;

                return array_merge($field, [
                    'options' => is_string($optionsKey)
                        ? ProspectScheduledActivityForm::optionsFor(
                            $optionsKey,
                            $optionsKey === 'appointment_types' ? $appointmentTypes : [],
                            $optionsKey === 'team_helpers' ? $this->teamHelperOptions() : [],
                            $purposeOptions,
                            $locationOptions,
                        )
                        : [],
                ]);
            })
            ->values()
            ->all();

        return view('livewire.prospects.prospect-schedule-activity-form', [
            'resolvedFields' => $resolvedFields,
            'quickScheduleTypes' => ProspectScheduledActivityForm::quickScheduleTypes(),
            'activityTypeLabel' => ProspectScheduledActivityForm::appointmentTypeLabel($typeId, $appointmentTypes),
            'createOptionValue' => ProspectScheduledActivityForm::CREATE_OPTION_VALUE,
            'isEditing' => filled($this->appointmentId),
        ]);
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function appointmentTypeOptions(): array
    {
        return AppointmentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug'])
            ->map(fn (AppointmentType $type): array => [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
                'slug' => (string) $type->slug,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function teamHelperOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return User::query()
            ->when(filled($user->team_id), fn ($query) => $query->where('team_id', $user->team_id))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name'])
            ->map(fn (User $helper): array => [
                'id' => (int) $helper->id,
                'label' => (string) $helper->name,
            ])
            ->all();
    }

    private function loadAppointment(int $appointmentId): void
    {
        $appointment = ProspectAppointment::query()
            ->whereKey($appointmentId)
            ->where('prospect_id', $this->prospectId)
            ->firstOrFail();

        $this->authorize('update', $appointment);
        $this->appointmentId = $appointment->id;
        $this->state = ProspectScheduledActivityForm::stateFromAppointment($appointment);
        $this->resetValidation();
    }

    private function prospect(): Prospect
    {
        $prospect = Prospect::query()->findOrFail($this->prospectId);
        $this->authorize('update', $prospect);

        return $prospect;
    }

    private function resetForm(): void
    {
        $this->appointmentId = null;
        $this->state = ProspectScheduledActivityForm::defaultState();
        $this->resetValidation();
    }
}
