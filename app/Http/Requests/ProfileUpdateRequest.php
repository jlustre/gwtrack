<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\ProfilePhotoService;
use App\Support\LocationOptions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['gw_associate_id', 'gw_invite_link'] as $field) {
            if ($this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profileId = $this->user()->profile()->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_province_id' => ['nullable', 'integer', 'exists:state_provinces,id'],
            'timezone_id' => ['nullable', 'integer', 'exists:timezones,id'],
            'best_contact_time' => ['nullable', 'string', Rule::in(array_keys(LocationOptions::contactTimes()))],
            'gw_associate_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('profiles', 'gw_associate_id')->ignore($profileId),
            ],
            'gw_invite_link' => [
                'nullable',
                'string',
                'max:2048',
                'url',
                Rule::unique('profiles', 'gw_invite_link')->ignore($profileId),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gw_invite_link.url' => 'Enter a valid Greatway invite URL (including https://).',
            'gw_invite_link.unique' => 'This Greatway invite link is already in use by another member.',
            'gw_associate_id.unique' => 'This GW Associate ID is already in use by another member.',
            'photo.image' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
            'photo.mimes' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
            'photo.max' => 'Profile photos must be 2 MB or smaller.',
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

            if ($validator->errors()->isNotEmpty()
                && $this->hasFile('photo')
                && ! $validator->errors()->has('photo')) {
                app(ProfilePhotoService::class)->stageForPreview(
                    $this->user(),
                    $this->file('photo'),
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('profile_feedback', [
            'type' => 'error',
            'message' => 'Please correct the highlighted fields and try again.',
        ]);

        if ($this->input('redirect_to') === 'dashboard') {
            session()->flash('show_profile_completion_modal', true);
        }

        if ($this->hasFile('photo') && ! $validator->errors()->has('photo')) {
            app(ProfilePhotoService::class)->stageForPreview(
                $this->user(),
                $this->file('photo'),
            );
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(
                $this->input('redirect_to') === 'dashboard'
                    ? route('dashboard')
                    : route('profile.edit', ['tab' => 'profile'])
            );
    }
}
