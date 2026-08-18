<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ProspectScheduledActivityForm
{
    public const CREATE_OPTION_VALUE = '__new__';

    /**
     * @return array<string, string>
     */
    public static function quickScheduleTypes(): array
    {
        return config('prospect-scheduled-activity-form.quick_schedule_types', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fieldDefinitions(): array
    {
        return config('prospect-scheduled-activity-form.fields', []);
    }

    /**
     * @param  list<array{id: int|string, name: string, slug?: string}>  $appointmentTypes
     * @param  list<array{id: int|string, label: string}>  $teamHelpers
     * @param  array<string, string>  $purposeOptions
     * @param  array<string, string>  $locationOptions
     * @return array<string, mixed>
     */
    public static function optionsFor(
        string $optionsKey,
        array $appointmentTypes = [],
        array $teamHelpers = [],
        array $purposeOptions = [],
        array $locationOptions = [],
    ): array {
        return match ($optionsKey) {
            'appointment_types' => collect($appointmentTypes)
                ->mapWithKeys(fn (array $type): array => [(string) $type['id'] => (string) $type['name']])
                ->all(),
            'team_helpers' => collect($teamHelpers)
                ->mapWithKeys(fn (array $helper): array => [(string) $helper['id'] => (string) $helper['label']])
                ->all(),
            'purpose_options' => $purposeOptions,
            'location_options' => $locationOptions,
            default => [],
        };
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public static function validationRules(): array
    {
        $rules = [];

        foreach (self::fieldDefinitions() as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $rules[$key] = self::rulesForField($field);
        }

        return $rules;
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public static function livewireValidationRules(): array
    {
        $rules = collect(self::validationRules())
            ->mapWithKeys(fn (array $fieldRules, string $key): array => ["state.{$key}" => $fieldRules])
            ->all();

        foreach (self::creatableFieldDefinitions() as $field) {
            $createKey = (string) ($field['create_field_key'] ?? '');
            $max = (int) ($field['max'] ?? 500);

            if ($createKey === '') {
                continue;
            }

            $rules["state.{$createKey}"] = [
                'nullable',
                'string',
                'max:'.$max,
                'required_if:state.'.($field['key'] ?? '').','.self::CREATE_OPTION_VALUE,
            ];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function resolveCreatableState(array $state): array
    {
        foreach (self::creatableFieldDefinitions() as $field) {
            $key = (string) ($field['key'] ?? '');
            $createKey = (string) ($field['create_field_key'] ?? '');

            if ($key === '' || $createKey === '') {
                continue;
            }

            if (($state[$key] ?? null) === self::CREATE_OPTION_VALUE) {
                $state[$key] = trim((string) ($state[$createKey] ?? ''));
            }

            unset($state[$createKey]);
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<array{category: string, value: string}>
     */
    public static function newlyCreatedOptions(array $state): array
    {
        $created = [];

        foreach (self::creatableFieldDefinitions() as $field) {
            $key = (string) ($field['key'] ?? '');
            $createKey = (string) ($field['create_field_key'] ?? '');
            $category = (string) ($field['options_category'] ?? '');

            if ($key === '' || $createKey === '' || $category === '') {
                continue;
            }

            if (($state[$key] ?? null) !== self::CREATE_OPTION_VALUE) {
                continue;
            }

            $value = trim((string) ($state[$createKey] ?? ''));

            if ($value !== '') {
                $created[] = [
                    'category' => $category,
                    'value' => $value,
                ];
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function toSchedulePayload(array $validated): array
    {
        $payload = [];

        foreach (self::fieldDefinitions() as $field) {
            $formKey = (string) ($field['key'] ?? '');
            $payloadKey = (string) ($field['payload_key'] ?? $formKey);

            if ($formKey === '' || $payloadKey === '' || ! array_key_exists($formKey, $validated)) {
                continue;
            }

            $value = self::normalizePayloadValue($payloadKey, $validated[$formKey]);

            if ($value !== null) {
                $payload[$payloadKey] = $value;
            }
        }

        $payload['sync_calendar'] = (bool) ($validated['sync_calendar'] ?? config('prospect-scheduled-activity-form.sync.calendar', true));
        $payload['create_prep_task'] = (bool) ($validated['create_prep_task'] ?? config('prospect-scheduled-activity-form.sync.prep_task', true));

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultState(?int $defaultAppointmentTypeId = null): array
    {
        $state = [];

        foreach (self::fieldDefinitions() as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $state[$key] = match ($key) {
                'appointment_type_id' => $defaultAppointmentTypeId,
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
                'duration_minutes' => (int) ($field['default'] ?? 60),
                'create_prep_task' => (bool) ($field['default'] ?? config('prospect-scheduled-activity-form.sync.prep_task', true)),
                'sync_calendar' => (bool) ($field['default'] ?? config('prospect-scheduled-activity-form.sync.calendar', true)),
                default => $field['default'] ?? null,
            };
        }

        foreach (self::creatableFieldDefinitions() as $field) {
            $createKey = (string) ($field['create_field_key'] ?? '');

            if ($createKey !== '') {
                $state[$createKey] = '';
            }
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stateFromAppointment(\App\Models\ProspectAppointment $appointment): array
    {
        $state = self::defaultState();
        $state['appointment_type_id'] = $appointment->appointment_type_id ? (string) $appointment->appointment_type_id : null;
        $state['scheduled_at'] = $appointment->scheduled_at?->format('Y-m-d\TH:i') ?? now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $state['purpose'] = $appointment->purpose ?? '';
        $state['location_or_link'] = $appointment->location_or_link ?? '';
        $state['assigned_helper_id'] = $appointment->assigned_helper_id ? (string) $appointment->assigned_helper_id : null;
        $state['notes'] = $appointment->notes ?? '';
        $state['create_prep_task'] = false;
        $state['sync_calendar'] = true;

        return $state;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function creatableFieldDefinitions(): array
    {
        return collect(self::fieldDefinitions())
            ->filter(fn (array $field): bool => ($field['type'] ?? '') === 'select_creatable')
            ->values()
            ->all();
    }

    public static function appointmentTypeLabel(?int $appointmentTypeId, array $appointmentTypes = []): string
    {
        if (! filled($appointmentTypeId)) {
            return 'Scheduled Activity';
        }

        foreach ($appointmentTypes as $type) {
            if ((int) ($type['id'] ?? 0) === (int) $appointmentTypeId) {
                return (string) ($type['name'] ?? 'Scheduled Activity');
            }
        }

        return 'Scheduled Activity';
    }

    public static function appointmentTypeIdForSlug(string $slug, array $appointmentTypes): ?int
    {
        foreach ($appointmentTypes as $type) {
            if (($type['slug'] ?? '') === $slug) {
                return (int) $type['id'];
            }
        }

        return null;
    }

    private static function normalizePayloadValue(string $payloadKey, mixed $value): mixed
    {
        if (in_array($payloadKey, ['appointment_type_id', 'assigned_helper_id', 'duration_minutes'], true)) {
            return filled($value) ? (int) $value : null;
        }

        if (in_array($payloadKey, ['create_prep_task', 'sync_calendar'], true)) {
            return (bool) $value;
        }

        return filled($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<string|Rule>
     */
    private static function rulesForField(array $field): array
    {
        $rules = ['nullable'];

        if (($field['required'] ?? false) === true) {
            $rules = ['required'];
        }

        $optionsKey = $field['options_key'] ?? null;

        if ($optionsKey === 'appointment_types') {
            return array_merge($rules, ['integer', 'exists:appointment_types,id']);
        }

        if ($optionsKey === 'team_helpers') {
            return array_merge($rules, ['integer', 'exists:users,id']);
        }

        return match ($field['type'] ?? 'text') {
            'datetime' => array_merge($rules, ['date', 'after:now']),
            'number' => array_merge($rules, [
                'integer',
                'min:'.($field['min'] ?? 15),
                'max:'.($field['max'] ?? 480),
            ]),
            'textarea' => array_merge($rules, ['string', 'max:5000']),
            'checkbox' => ['boolean'],
            'select_creatable' => array_merge($rules, ['string', 'max:'.($field['max'] ?? 500)]),
            default => array_merge($rules, ['string', 'max:'.($field['max'] ?? 500)]),
        };
    }
}
