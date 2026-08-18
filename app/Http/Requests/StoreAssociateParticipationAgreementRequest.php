<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssociateParticipationAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('acknowledgment_accepted')) {
            $this->merge([
                'acknowledgment_accepted' => $this->boolean('acknowledgment_accepted'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'associate_id' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state_province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'acknowledgment_accepted' => ['accepted'],
            'associate_signature' => ['required', 'string', 'max:255'],
            'associate_signed_at' => ['nullable', 'date'],
        ];
    }
}
