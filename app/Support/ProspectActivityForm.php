<?php

namespace App\Support;

use App\Models\ProspectActivity;
use Illuminate\Validation\Rule;

class ProspectActivityForm
{
    /**
     * @return array<string, string>
     */
    public static function activityTypes(): array
    {
        return config('prospects.activity_types', []);
    }

    /**
     * @return array<string, string>
     */
    public static function quickActivityTypes(): array
    {
        $configured = config('prospect-activity-form.quick_activity_types', []);
        $available = self::activityTypes();

        return array_filter(
            $configured,
            fn (string $label, string $key): bool => array_key_exists($key, $available),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fieldDefinitions(): array
    {
        return config('prospect-activity-form.fields', []);
    }

    /**
     * @return list<string>
     */
    public static function fieldKeys(): array
    {
        return collect(self::fieldDefinitions())
            ->pluck('key')
            ->filter(fn ($key): bool => is_string($key) && $key !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function outcomeSuggestions(): array
    {
        $key = config('prospect-activity-form.outcome_suggestions');

        if (! is_string($key)) {
            return [];
        }

        return array_values(config($key, []));
    }

    /**
     * @param  list<array{id: int|string, label: string}>|list<array<string, mixed>>  $pipelineStages
     * @return array<string, mixed>
     */
    public static function optionsFor(string $optionsKey, array $pipelineStages = []): array
    {
        return match ($optionsKey) {
            'activity_types' => self::activityTypes(),
            'pipeline_stages' => collect($pipelineStages)
                ->mapWithKeys(fn (array $stage): array => [(string) $stage['id'] => (string) $stage['label']])
                ->all(),
            'outcome_suggestions' => array_combine(
                self::outcomeSuggestions(),
                self::outcomeSuggestions(),
            ) ?? [],
            default => [],
        };
    }

    /**
     * Livewire validation with state.* prefixed keys.
     *
     * @return array<string, list<string|Rule>>
     */
    public static function livewireValidationRules(): array
    {
        return collect(self::validationRules())
            ->mapWithKeys(fn (array $rules, string $key): array => ["state.{$key}" => $rules])
            ->all();
    }
    /**
     * Form state validation (flat keys matching config field keys).
     *
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
     * REST API validation (payload / service attribute names).
     *
     * @return array<string, list<string|Rule>>
     */
    public static function apiValidationRules(): array
    {
        $rules = [];

        foreach (self::fieldDefinitions() as $field) {
            $apiKey = self::apiKeyForField($field);

            if ($apiKey === '') {
                continue;
            }

            $rules[$apiKey] = self::rulesForField($field, apiContext: true);
        }

        return $rules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function apiFieldDefinitions(): array
    {
        return collect(self::fieldDefinitions())
            ->map(function (array $field): array {
                $apiKey = self::apiKeyForField($field);

                return array_merge($field, [
                    'api_key' => $apiKey,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function toLogPayload(array $validated): array
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

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function toLogPayloadFromApi(array $validated): array
    {
        $mapped = [];

        foreach (self::fieldDefinitions() as $field) {
            $formKey = (string) ($field['key'] ?? '');
            $apiKey = self::apiKeyForField($field);

            if ($formKey === '' || $apiKey === '' || ! array_key_exists($apiKey, $validated)) {
                continue;
            }

            $mapped[$formKey] = $validated[$apiKey];
        }

        return self::toLogPayload($mapped);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultState(?int $pipelineStageId = null, ?string $activityType = null): array
    {
        $state = [];

        foreach (self::fieldDefinitions() as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $state[$key] = match ($key) {
                'activity_type' => $activityType && array_key_exists($activityType, self::activityTypes())
                    ? $activityType
                    : (string) ($field['default'] ?? 'phone_call'),
                'activity_occurred_at' => now()->format('Y-m-d\TH:i'),
                'pipeline_stage_id' => $pipelineStageId,
                default => $field['default'] ?? null,
            };
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stateFromActivity(ProspectActivity $activity): array
    {
        $state = self::defaultState(
            isset($activity->metadata['pipeline_stage_id']) ? (int) $activity->metadata['pipeline_stage_id'] : null,
            $activity->activity_type,
        );

        $state['activity_subject'] = $activity->metadata['subject'] ?? '';
        $state['activity_occurred_at'] = $activity->occurred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $state['duration_minutes'] = $activity->duration_minutes;
        $state['activity_outcome'] = $activity->outcome ?? '';
        $state['activity_notes'] = $activity->notes ?? '';
        $state['activity_next_action'] = $activity->next_action ?? '';
        $state['activity_next_follow_up_at'] = $activity->next_follow_up_at?->format('Y-m-d\TH:i') ?? '';

        if (isset($activity->metadata['pipeline_stage_id'])) {
            $state['pipeline_stage_id'] = (string) $activity->metadata['pipeline_stage_id'];
        }

        return $state;
    }

    public static function activityTypeLabel(?string $activityType): string
    {
        if (! filled($activityType)) {
            return 'Activity';
        }

        return self::activityTypes()[$activityType]
            ?? str($activityType)->replace('_', ' ')->title()->toString();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function apiKeyForField(array $field): string
    {
        if (filled($field['api_key'] ?? null)) {
            return (string) $field['api_key'];
        }

        $payloadKey = (string) ($field['payload_key'] ?? $field['key'] ?? '');

        return $payloadKey;
    }

    private static function normalizePayloadValue(string $payloadKey, mixed $value): mixed
    {
        if ($payloadKey === 'pipeline_stage_id') {
            return filled($value) ? (int) $value : null;
        }

        if ($payloadKey === 'duration_minutes') {
            return filled($value) ? (int) $value : null;
        }

        if ($payloadKey === 'subject') {
            return filled($value) ? $value : null;
        }

        return filled($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<string|Rule>
     */
    private static function rulesForField(array $field, bool $apiContext = false): array
    {
        $rules = ['nullable'];

        if (($field['required'] ?? false) === true) {
            $rules = ['required'];
        }

        $optionsKey = $field['options_key'] ?? null;

        if ($optionsKey === 'activity_types') {
            return array_merge($rules, ['string', Rule::in(array_keys(self::activityTypes()))]);
        }

        if ($optionsKey === 'pipeline_stages') {
            return array_merge($rules, ['integer', 'exists:pipeline_stages,id']);
        }

        return match ($field['type'] ?? 'text') {
            'datetime' => array_merge($rules, ['date']),
            'number' => array_merge($rules, [
                'integer',
                'min:'.($field['min'] ?? 1),
                'max:'.($field['max'] ?? 600),
            ]),
            'textarea' => array_merge($rules, ['string', 'max:'.($field['max'] ?? 5000)]),
            default => array_merge($rules, ['string', 'max:'.($field['max'] ?? ($apiContext && ($field['payload_key'] ?? '') === 'outcome' ? 80 : 255))]),
        };
    }
}
