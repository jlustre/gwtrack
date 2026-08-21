<?php

namespace App\Http\Requests;

use App\Support\LocationOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateProfileInsuranceLicensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_licensed_life_insurance' => ['nullable', 'boolean'],
            'insurance_licenses' => [
                Rule::requiredIf(fn (): bool => $this->boolean('is_licensed_life_insurance')),
                'nullable',
                'array',
                'min:1',
            ],
            'insurance_licenses.*' => ['string', 'max:120'],
            'insurance_license_numbers' => ['nullable', 'array'],
            'insurance_license_numbers.*' => ['nullable', 'string', 'max:100'],
            'insurance_license_expirations' => ['nullable', 'array'],
            'insurance_license_expirations.*' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('insurance_licenses', []) as $index => $key) {
                if (! is_string($key) || ! LocationOptions::isValidJurisdictionKey($key)) {
                    $validator->errors()->add(
                        "insurance_licenses.{$index}",
                        'Each license must be a valid province or state in a supported country.'
                    );
                }
            }

            if (! $this->boolean('is_licensed_life_insurance')) {
                return;
            }

            $numbers = $this->input('insurance_license_numbers', []);
            $numbers = is_array($numbers) ? $numbers : [];
            $expirations = $this->input('insurance_license_expirations', []);
            $expirations = is_array($expirations) ? $expirations : [];

            foreach ($this->normalizedInsuranceLicenses() as $key) {
                $label = LocationOptions::labelsForJurisdictionKeys([$key])[0] ?? $key;
                $number = trim((string) ($numbers[$key] ?? ''));
                $expiration = LocationOptions::normalizeLicenseExpirationDate((string) ($expirations[$key] ?? ''));

                if ($number === '') {
                    $validator->errors()->add(
                        "insurance_license_numbers.{$key}",
                        "Enter the license number for {$label}."
                    );
                }

                if ($expiration === '') {
                    $validator->errors()->add(
                        "insurance_license_expirations.{$key}",
                        "Enter the expiration date for {$label}."
                    );
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    public function normalizedInsuranceLicenses(): array
    {
        if (! $this->boolean('is_licensed_life_insurance')) {
            return [];
        }

        return LocationOptions::normalizeLicensedJurisdictionKeys(
            $this->input('insurance_licenses', [])
        );
    }

    /**
     * @return array<string, string>
     */
    public function normalizedInsuranceLicenseNumbers(): array
    {
        if (! $this->boolean('is_licensed_life_insurance')) {
            return [];
        }

        return LocationOptions::normalizeLicenseNumbers(
            $this->normalizedInsuranceLicenses(),
            is_array($this->input('insurance_license_numbers', [])) ? $this->input('insurance_license_numbers', []) : []
        );
    }

    /**
     * @return array<string, string>
     */
    public function normalizedInsuranceLicenseExpirations(): array
    {
        if (! $this->boolean('is_licensed_life_insurance')) {
            return [];
        }

        return LocationOptions::normalizeLicenseExpirations(
            $this->normalizedInsuranceLicenses(),
            is_array($this->input('insurance_license_expirations', [])) ? $this->input('insurance_license_expirations', []) : []
        );
    }

    public function isLicensedLifeInsurance(): bool
    {
        return $this->boolean('is_licensed_life_insurance');
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('licenses_feedback', [
            'type' => 'error',
            'message' => 'Please correct the selected licenses and try again.',
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('profile.edit', ['tab' => 'licenses']));
    }
}
