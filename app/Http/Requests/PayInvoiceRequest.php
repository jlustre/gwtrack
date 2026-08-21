<?php

namespace App\Http\Requests;

use App\Models\SubscriptionInvoice;
use App\Services\InvoicePaymentService;
use App\Services\MonetizationSettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        if (! $invoice instanceof SubscriptionInvoice || $this->user() === null) {
            return false;
        }

        if (! app(MonetizationSettingsService::class)->sessionEnabled()) {
            return false;
        }

        return app(InvoicePaymentService::class)->canPayInvoice($this->user(), $invoice);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'card_number' => preg_replace('/\D+/', '', (string) $this->input('card_number', '')) ?? '',
            'card_cvc' => preg_replace('/\D+/', '', (string) $this->input('card_cvc', '')) ?? '',
            'card_expiry' => trim((string) $this->input('card_expiry', '')),
            'cardholder_name' => trim((string) $this->input('cardholder_name', '')),
            'enable_auto_pay' => $this->boolean('enable_auto_pay'),
        ]);
    }

    public function rules(): array
    {
        return [
            'cardholder_name' => ['required', 'string', 'max:120'],
            'card_number' => ['required', 'string', 'min:13', 'max:19'],
            'card_expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\s*\/\s*\d{2}$/'],
            'card_cvc' => ['required', 'string', 'min:3', 'max:4'],
            'enable_auto_pay' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cardholder_name.required' => 'Enter the name on the card.',
            'card_number.required' => 'Enter the card number.',
            'card_expiry.required' => 'Enter the expiration date as MM/YY.',
            'card_expiry.regex' => 'Enter the expiration date as MM/YY.',
            'card_cvc.required' => 'Enter the security code.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payments = app(InvoicePaymentService::class);
            $digits = (string) $this->input('card_number', '');

            if ($digits !== '' && ! $payments->passesLuhn($digits)) {
                $validator->errors()->add('card_number', 'Enter a valid card number.');
            }

            $expiry = (string) $this->input('card_expiry', '');

            if (preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $expiry, $matches) !== 1) {
                return;
            }

            $month = (int) $matches[1];
            $year = 2000 + (int) $matches[2];
            $expires = Carbon::create($year, $month, 1)->endOfMonth();

            if ($expires->lt(now()->startOfDay())) {
                $validator->errors()->add('card_expiry', 'This card has expired.');
            }
        });
    }
}
