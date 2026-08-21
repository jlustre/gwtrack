<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    public const STATUS_COMPLIMENTARY = 'complimentary';

    public const STATUS_VOID = 'void';

    public const METHOD_CARD = 'card';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_CHECK = 'check';

    public const METHOD_COMPLIMENTARY = 'complimentary';

    protected $fillable = [
        'user_id',
        'user_subscription_id',
        'number',
        'rank_code',
        'rank_label',
        'billing_interval',
        'amount',
        'currency',
        'status',
        'billed_at',
        'period_start',
        'period_end',
        'description',
        'paid_at',
        'paid_by',
        'payment_method',
        'payment_reference',
        'payment_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'billed_at' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function intervalLabel(): string
    {
        return UserSubscription::intervalLabel((string) ($this->billing_interval ?: UserSubscription::INTERVAL_MONTHLY));
    }

    public function isAnnual(): bool
    {
        return ($this->billing_interval ?: UserSubscription::INTERVAL_MONTHLY) === UserSubscription::INTERVAL_ANNUAL;
    }

    public function amountLabel(): string
    {
        return '$'.number_format((float) $this->amount, 2);
    }

    public function isPayable(): bool
    {
        return $this->status === self::STATUS_ISSUED && (float) $this->amount > 0;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_COMPLIMENTARY => 'Free',
            self::STATUS_VOID => 'Void',
            default => 'Due',
        };
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CARD => 'Card',
            self::METHOD_BANK_TRANSFER => 'Bank transfer',
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHECK => 'Check',
            self::METHOD_COMPLIMENTARY => 'Complimentary',
            default => $this->payment_method ? ucfirst(str_replace('_', ' ', $this->payment_method)) : 'Not recorded',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethodOptions(): array
    {
        return [
            self::METHOD_CARD => 'Card',
            self::METHOD_BANK_TRANSFER => 'Bank transfer',
            self::METHOD_CASH => 'Cash',
            self::METHOD_CHECK => 'Check',
            self::METHOD_COMPLIMENTARY => 'Complimentary',
        ];
    }

    public function periodLabel(): string
    {
        return $this->period_start->format('M j, Y').' – '.$this->period_end->format('M j, Y');
    }
}
