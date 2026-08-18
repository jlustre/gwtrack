<?php

namespace App\Http\Requests;

use App\Support\LocationOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateCfmPortalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->hasRole('certified-field-mentor') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'manual_unavailable' => $this->boolean('manual_unavailable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_province_id' => ['nullable', 'integer', 'exists:state_provinces,id'],
            'timezone_id' => ['nullable', 'integer', 'exists:timezones,id'],
            'mentor_bio' => ['nullable', 'string', 'max:2000'],
            'languages' => ['nullable', 'string', 'max:500'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'manual_unavailable' => ['sometimes', 'boolean'],
            'licensed_jurisdictions' => ['nullable', 'array'],
            'licensed_jurisdictions.*' => ['string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $countryId = $this->integer('country_id') ?: null;
            $stateProvinceId = $this->integer('state_province_id') ?: null;

            if (! LocationOptions::isValidStateProvinceId($countryId, $stateProvinceId)) {
                $validator->errors()->add('state_province_id', 'Select a valid province or state for the chosen country.');
            }

            foreach ($this->input('licensed_jurisdictions', []) as $index => $key) {
                if (! is_string($key) || ! LocationOptions::isValidJurisdictionKey($key)) {
                    $validator->errors()->add(
                        "licensed_jurisdictions.{$index}",
                        'Each licensed jurisdiction must be a valid province or state.'
                    );
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    public function normalizedLicensedJurisdictions(): array
    {
        return LocationOptions::normalizeLicensedJurisdictionKeys(
            $this->input('licensed_jurisdictions', [])
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('open_edit_profile_modal', true);
        session()->flash('profile_feedback', [
            'type' => 'error',
            'message' => 'Please correct the highlighted fields and try again.',
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('cfm.portal'));
    }
}
