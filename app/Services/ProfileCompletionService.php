<?php

namespace App\Services;

use App\Models\ProfileCompletionField;
use App\Models\User;

class ProfileCompletionService
{
    /**
     * @return list<array{key: string, label: string, filled: bool}>
     */
    public function fields(User $user): array
    {
        $user->loadMissing('profile');
        $profile = $user->profile;

        return ProfileCompletionField::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(function (ProfileCompletionField $field) use ($user, $profile): array {
                $source = $field->source
                    ?? ProfileCompletionField::definitions()[$field->field_key]['source']
                    ?? 'profile';

                $value = $source === 'user'
                    ? $user->getAttribute($field->field_key)
                    : $this->profileFieldValue($profile, $field->field_key);

                return [
                    'key' => $field->field_key,
                    'label' => $field->label,
                    'filled' => filled($value),
                ];
            })
            ->values()
            ->all();
    }

    public function percent(User $user): int
    {
        $fields = $this->fields($user);

        if ($fields === []) {
            return 100;
        }

        $filled = collect($fields)->where('filled', true)->count();

        return (int) round(($filled / count($fields)) * 100);
    }

    public function isComplete(User $user): bool
    {
        $fields = $this->fields($user);

        if ($fields === []) {
            return true;
        }

        return collect($fields)->every(fn (array $field): bool => $field['filled']);
    }

    /**
     * @return array{
     *     percent: int,
     *     is_complete: bool,
     *     fields: list<array{key: string, label: string, filled: bool}>
     * }
     */
    public function snapshot(User $user): array
    {
        $fields = $this->fields($user);

        return [
            'percent' => $this->percent($user),
            'is_complete' => $fields === [] || collect($fields)->every(fn (array $field): bool => $field['filled']),
            'fields' => $fields,
        ];
    }

    private function profileFieldValue(?\App\Models\Profile $profile, string $fieldKey): mixed
    {
        if ($profile === null) {
            return null;
        }

        $attribute = match ($fieldKey) {
            'country' => 'country_id',
            'province' => 'state_province_id',
            'timezone' => 'timezone_id',
            default => $fieldKey,
        };

        return $profile->getAttribute($attribute);
    }
}
