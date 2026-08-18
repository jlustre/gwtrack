<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberProductionEntryRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'policy_reference' => ['nullable', 'string', 'max:255'],
            'annual_premium' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'posted_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Enter a description for this production entry.',
            'annual_premium.required' => 'Enter the annual premium amount.',
            'annual_premium.min' => 'Annual premium must be greater than zero.',
        ];
    }
}
