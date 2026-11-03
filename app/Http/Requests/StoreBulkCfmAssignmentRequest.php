<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkCfmAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessCfmManagement() ?? false;
    }

    public function rules(): array
    {
        return [
            'associate_ids' => ['required', 'array', 'min:1', 'max:50'],
            'associate_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'cfm_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
