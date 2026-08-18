<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ProspectFormRules
{
    /**
     * @return array<string, mixed>
     */
    public static function profileRules(): array
    {
        return [
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'employer_business' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(array_keys(config('prospects.genders', [])))],
            'marital_status' => ['nullable', 'string', Rule::in(array_keys(config('prospects.marital_statuses', [])))],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'spouse_occupation' => ['nullable', 'string', 'max:120'],
            'spouse_date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'dependents' => ['nullable', 'array', 'max:12'],
            'dependents.*.name' => ['nullable', 'string', 'max:255'],
            'dependents.*.age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'qualification_traits' => ['nullable', 'array'],
            'qualification_traits.*' => ['string', Rule::in(ProspectQualificationTraits::allowedKeys())],
            'qualification_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function normalizeProfileAttributes(array $validated): array
    {
        if (array_key_exists('qualification_traits', $validated)) {
            $validated['qualification_traits'] = ProspectQualificationTraits::normalize($validated['qualification_traits'] ?? []);
        }

        if (array_key_exists('dependents', $validated)) {
            $validated['dependents'] = ProspectDependents::normalize($validated['dependents'] ?? []);
        }

        return $validated;
    }
}
