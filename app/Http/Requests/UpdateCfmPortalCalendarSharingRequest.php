<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCfmPortalCalendarSharingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('certified-field-mentor') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'share_calendar_with_apprentices' => $this->boolean('share_calendar_with_apprentices'),
            'share_calendar_with_agency_owner' => $this->boolean('share_calendar_with_agency_owner'),
        ]);
    }

    public function rules(): array
    {
        return [
            'share_calendar_with_apprentices' => ['sometimes', 'boolean'],
            'share_calendar_with_agency_owner' => ['sometimes', 'boolean'],
        ];
    }
}
