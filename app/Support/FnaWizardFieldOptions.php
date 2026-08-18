<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class FnaWizardFieldOptions
{
    /**
     * @return array<string, string>
     */
    public static function genders(): array
    {
        return config('prospects.genders', []);
    }

    /**
     * @return array<string, string>
     */
    public static function maritalStatuses(): array
    {
        return config('prospects.marital_statuses', []);
    }

    /**
     * @return array<string, string>
     */
    public static function countries(): array
    {
        return collect(LocationOptions::countries())
            ->mapWithKeys(fn (string $country): array => [$country => $country])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function provincesFor(?string $country): array
    {
        if (! filled($country)) {
            return [];
        }

        return LocationOptions::provincesFor($country);
    }

    /**
     * @return array<string, string>
     */
    public static function preferredContactMethods(): array
    {
        return config('fna.preferred_contact_methods', []);
    }

    /**
     * @return array<string, string>
     */
    public static function contactTimes(): array
    {
        return LocationOptions::contactTimes();
    }

    /**
     * @return list<string>
     */
    public static function requiredFieldsForStep(int $step, bool $clientPortal = false): array
    {
        if (! $clientPortal) {
            return [];
        }

        return config("fna.client_portal_step_required_fields.{$step}", []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRulesForStep(int $step, bool $clientPortal = false): array
    {
        if (! $clientPortal || $step !== 1) {
            return [];
        }

        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_phone' => ['required', 'string', 'max:60'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(array_keys(self::genders()))],
            'marital_status' => ['required', Rule::in(array_keys(self::maritalStatuses()))],
            'country' => ['required', Rule::in(array_keys(self::countries()))],
            'state_province' => ['required', 'string', 'max:120'],
            'preferred_contact_method' => ['required', Rule::in(array_keys(self::preferredContactMethods()))],
            'best_contact_time' => ['required', Rule::in(array_keys(self::contactTimes()))],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer_business' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forWizard(?string $country = null, bool $clientPortal = false, int $step = 1): array
    {
        return [
            'genders' => self::genders(),
            'maritalStatuses' => self::maritalStatuses(),
            'countries' => self::countries(),
            'stateProvinces' => self::provincesFor($country),
            'preferredContactMethods' => self::preferredContactMethods(),
            'contactTimes' => self::contactTimes(),
            'requiredFields' => self::requiredFieldsForStep($step, $clientPortal),
        ];
    }
}
