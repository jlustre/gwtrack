<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCfmNominationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view team') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'require_approval' => $this->boolean('require_approval'),
            'notify_candidate' => $this->boolean('notify_candidate'),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'target_rank' => ['required', 'string', Rule::in([
                'Associate Mentor',
                'CFM I',
                'CFM II',
                'Senior CFM',
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'require_approval' => ['sometimes', 'boolean'],
            'notify_candidate' => ['sometimes', 'boolean'],
        ];
    }
}
