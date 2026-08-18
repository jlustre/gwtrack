<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateProfileInviteLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('gw_details_feedback', [
            'type' => 'error',
            'message' => 'Please correct the GW details and try again.',
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('profile.edit'));
    }
}
