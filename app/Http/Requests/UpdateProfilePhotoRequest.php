<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Please choose a profile photo to upload.',
            'photo.image' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
            'photo.mimes' => 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
            'photo.max' => 'Profile photos must be 2 MB or smaller.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new ValidationException($validator);
        }

        session()->flash('profile_feedback', [
            'type' => 'error',
            'message' => $validator->errors()->first('photo')
                ?? 'Please choose a valid profile photo (JPEG, PNG, or WebP, up to 2 MB).',
        ]);

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('profile.edit', ['tab' => 'profile']));
    }
}
