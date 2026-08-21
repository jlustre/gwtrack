<?php

namespace App\Services;

use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InvoicePaymentService
{
    public function __construct(
        private readonly MonetizationSettingsService $monetization,
    ) {}

    public function canProcessPayments(User $actor): bool
    {
        return $this->monetization->sessionEnabled()
            && $actor->hasAnyRole(['super-admin', 'admin', 'agency-owner']);
    }

    public function canPayInvoice(User $actor, SubscriptionInvoice $invoice): bool
    {
        if (! $this->monetization->sessionEnabled()) {
            return false;
        }

        return $invoice->user_id === $actor->id || $this->canProcessPayments($actor);
    }

    public function payWithCard(
        SubscriptionInvoice $invoice,
        User $payer,
        string $cardNumber,
        bool $enableAutoPay = false,
    ): SubscriptionInvoice {
        abort_unless($this->canPayInvoice($payer, $invoice), 403);
        $this->ensurePayable($invoice);

        $digits = preg_replace('/\D+/', '', $cardNumber) ?? '';
        $lastFour = substr($digits, -4);
        $brand = $this->cardBrand($digits);

        $paid = $this->markPaid(
            $invoice,
            $payer,
            SubscriptionInvoice::METHOD_CARD,
            trim($brand.' •••• '.$lastFour),
        );

        if ($enableAutoPay) {
            $this->storeAutoPayCard($payer, $brand, $lastFour);
        }

        return $paid;
    }

    public function applyStoredAutoPay(SubscriptionInvoice $invoice, UserSubscription $subscription): ?SubscriptionInvoice
    {
        if (! $invoice->isPayable() || ! $subscription->hasAutoPayCard()) {
            return null;
        }

        $payer = $subscription->user ?? $invoice->user;

        if ($payer === null) {
            return null;
        }

        return $this->markPaid(
            $invoice,
            $payer,
            SubscriptionInvoice::METHOD_CARD,
            $subscription->autoPayCardLabel(),
            'Automatic '.$invoice->intervalLabel().' payment.',
        );
    }

    public function storeAutoPayCard(User $user, string $brand, string $lastFour): void
    {
        $subscription = $user->subscription
            ?? UserSubscription::query()->firstOrNew(['user_id' => $user->id]);

        $subscription->fill([
            'auto_pay' => true,
            'auto_pay_brand' => $brand,
            'auto_pay_last_four' => $lastFour,
        ]);

        if (! filled($subscription->billing_interval)) {
            $subscription->billing_interval = UserSubscription::INTERVAL_MONTHLY;
        }

        $subscription->save();
        $user->setRelation('subscription', $subscription);
    }

    public function recordPayment(
        SubscriptionInvoice $invoice,
        User $actor,
        string $method,
        ?string $reference = null,
        ?string $notes = null,
    ): SubscriptionInvoice {
        abort_unless($this->canProcessPayments($actor), 403);
        $this->ensurePayable($invoice);

        abort_unless(array_key_exists($method, SubscriptionInvoice::paymentMethodOptions()), 422);

        if ($method === SubscriptionInvoice::METHOD_COMPLIMENTARY) {
            $invoice->forceFill([
                'status' => SubscriptionInvoice::STATUS_COMPLIMENTARY,
                'paid_at' => now(),
                'paid_by' => $actor->id,
                'payment_method' => $method,
                'payment_reference' => filled($reference) ? $reference : null,
                'payment_notes' => filled($notes) ? $notes : 'Marked complimentary by '.$actor->name.'.',
            ])->save();

            return $invoice->fresh(['user', 'payer']) ?? $invoice;
        }

        return $this->markPaid($invoice, $actor, $method, $reference, $notes);
    }

    public function voidInvoice(SubscriptionInvoice $invoice, User $actor, ?string $notes = null): SubscriptionInvoice
    {
        abort_unless($this->canProcessPayments($actor), 403);
        $this->ensurePayable($invoice);

        $invoice->forceFill([
            'status' => SubscriptionInvoice::STATUS_VOID,
            'payment_method' => null,
            'payment_reference' => null,
            'payment_notes' => filled($notes) ? $notes : 'Voided by '.$actor->name.'.',
            'paid_at' => null,
            'paid_by' => $actor->id,
        ])->save();

        return $invoice->fresh(['user', 'payer']) ?? $invoice;
    }

    /**
     * @return array{outstanding_count: int, outstanding_amount: float, paid_this_month_count: int, paid_this_month_amount: float, collected_amount: float}
     */
    public function stats(): array
    {
        $monthStart = now()->startOfMonth();

        $outstanding = SubscriptionInvoice::query()->where('status', SubscriptionInvoice::STATUS_ISSUED);
        $paidThisMonth = SubscriptionInvoice::query()
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->where('paid_at', '>=', $monthStart);
        $collected = SubscriptionInvoice::query()->where('status', SubscriptionInvoice::STATUS_PAID);

        return [
            'outstanding_count' => (clone $outstanding)->count(),
            'outstanding_amount' => round((float) (clone $outstanding)->sum('amount'), 2),
            'paid_this_month_count' => (clone $paidThisMonth)->count(),
            'paid_this_month_amount' => round((float) (clone $paidThisMonth)->sum('amount'), 2),
            'collected_amount' => round((float) $collected->sum('amount'), 2),
        ];
    }

    public function invoicesQuery(?string $status = 'outstanding', ?string $search = null): Builder
    {
        $query = SubscriptionInvoice::query()
            ->with(['user:id,name,email,rank_id', 'user.rank:id,code,name', 'payer:id,name'])
            ->orderByDesc('billed_at')
            ->orderByDesc('id');

        $status = $status ?: 'outstanding';

        match ($status) {
            'paid' => $query->where('status', SubscriptionInvoice::STATUS_PAID),
            'all' => $query,
            default => $query->where('status', SubscriptionInvoice::STATUS_ISSUED),
        };

        $term = trim((string) $search);

        if ($term !== '') {
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('number', 'like', '%'.$term.'%')
                    ->orWhere('rank_label', 'like', '%'.$term.'%')
                    ->orWhereHas('user', function (Builder $users) use ($term): void {
                        $users->where('name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    });
            });
        }

        return $query;
    }

    public function cardBrand(string $digits): string
    {
        $first = substr($digits, 0, 1);

        return match ($first) {
            '4' => 'Visa',
            '5' => 'Mastercard',
            '3' => 'Amex',
            '6' => 'Discover',
            default => 'Card',
        };
    }

    public function passesLuhn(string $digits): bool
    {
        if ($digits === '' || ! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;
        $alternate = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];

            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }

            $sum += $n;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    private function markPaid(
        SubscriptionInvoice $invoice,
        User $actor,
        string $method,
        ?string $reference = null,
        ?string $notes = null,
    ): SubscriptionInvoice {
        $invoice->forceFill([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
            'paid_by' => $actor->id,
            'payment_method' => $method,
            'payment_reference' => filled($reference) ? $reference : null,
            'payment_notes' => filled($notes) ? $notes : null,
        ])->save();

        return $invoice->fresh(['user', 'payer']) ?? $invoice;
    }

    private function ensurePayable(SubscriptionInvoice $invoice): void
    {
        if ($invoice->isPayable()) {
            return;
        }

        throw ValidationException::withMessages([
            'invoice' => 'This invoice is no longer open for payment.',
        ]);
    }
}
