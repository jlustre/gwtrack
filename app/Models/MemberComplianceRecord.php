<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberComplianceRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'compliance_type',
        'title',
        'jurisdiction_key',
        'identifier',
        'status',
        'effective_date',
        'expiration_date',
        'renewal_window_days',
        'credits_required',
        'credits_earned',
        'carrier_name',
        'notes',
        'verified_at',
        'verified_by',
        'last_reminder_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiration_date' => 'date',
            'credits_required' => 'decimal:2',
            'credits_earned' => 'decimal:2',
            'verified_at' => 'datetime',
            'last_reminder_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function typeLabel(): string
    {
        return config('compliance-lifecycle.types.'.$this->compliance_type.'.label', ucfirst(str_replace('_', ' ', $this->compliance_type)));
    }

    public function statusLabel(): string
    {
        return config('compliance-lifecycle.statuses.'.$this->status, ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function daysUntilExpiration(): ?int
    {
        if ($this->expiration_date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiration_date, false);
    }
}
