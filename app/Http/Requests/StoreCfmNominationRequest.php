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
        ];
    }
}
