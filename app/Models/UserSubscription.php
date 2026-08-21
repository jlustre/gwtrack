<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    public const INTERVAL_MONTHLY = 'monthly';

    public const INTERVAL_ANNUAL = 'annual';

    protected $fillable = [
        'user_id',
        'billed_rank_code',
        'pending_rank_code',
        'billing_interval',
        'pending_billing_interval',
        'auto_pay',
        'auto_pay_brand',
        'auto_pay_last_four',
        'anniversary_day',
        'started_at',
        'next_billing_at',
        'last_anniversary_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'auto_pay' => 'boolean',
            'anniversary_day' => 'integer',
            'started_at' => 'date',
            'next_billing_at' => 'date',
            'last_anniversary_applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingInterval(): string
    {
        return $this->billing_interval === self::INTERVAL_ANNUAL
            ? self::INTERVAL_ANNUAL
            : self::INTERVAL_MONTHLY;
    }

    public function isAnnual(): bool
    {
        return $this->billingInterval() === self::INTERVAL_ANNUAL;
    }

    public function hasAutoPayCard(): bool
    {
        return (bool) $this->auto_pay && filled($this->auto_pay_last_four);
    }

    public function autoPayCardLabel(): ?string
    {
        if (! filled($this->auto_pay_last_four)) {
            return null;
        }

        $brand = filled($this->auto_pay_brand) ? $this->auto_pay_brand : 'Card';

        return $brand.' •••• '.$this->auto_pay_last_four;
    }

    public function planKey(): string
    {
        return $this->isAnnual() ? 'annual' : 'monthly_auto';
    }

    public static function intervalLabel(string $interval): string
    {
        return $interval === self::INTERVAL_ANNUAL ? 'Annual' : 'Monthly';
    }
}
