<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCfmAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view team') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('notify_cfm')) {
            $merge['notify_cfm'] = $this->boolean('notify_cfm');
        }

        if ($this->has('notify_associate')) {
            $merge['notify_associate'] = $this->boolean('notify_associate');
        }

        if ($this->has('require_cfm_approval')) {
            $merge['require_cfm_approval'] = $this->boolean('require_cfm_approval');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'associate_id' => ['required', 'integer', 'exists:users,id'],
            'cfm_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'notify_cfm' => ['sometimes', 'boolean'],
            'notify_associate' => ['sometimes', 'boolean'],
            'require_cfm_approval' => ['sometimes', 'boolean'],
        ];
    }
}
