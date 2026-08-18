<?php

namespace App\Http\Requests;

use App\Support\LocationOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateCfmLicensedJurisdictionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessCfmManagement() ?? false;
    }

    public function rules(): array
    {
        return [
            'licensed_jurisdictions' => ['nullable', 'array'],
            'licensed_jurisdictions.*' => ['string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
        session()->flash('open_cfm_profile_panel', true);
        session()->flash('open_cfm_licensed_edit', true);
        session()->flash('cfm_licensed_feedback', [
            'type' => 'error',
            'message' => 'Please correct the licensed jurisdictions and try again.',
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('team.cfms', ['cfm' => $this->route('user')?->id]));
    }
}
