<?php

namespace App\Http\Requests;

use App\Services\MonetizationSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(MonetizationSettingsService::class)->sessionEnabled();
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', 'in:monthly_auto,annual'],
        ];
    }
}
