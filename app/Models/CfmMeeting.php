<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CfmMeeting extends Model
{
    public const TYPES = [
        'coaching',
        'fap_review',
        'licensing',
        'onboarding',
        'promotion',
        'training',
        'general',
    ];

    public const STATUSES = [
        'scheduled',
        'completed',
        'cancelled',
        'no_show',
    ];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'booking_id',
        'type',
        'title',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CfmMeetingNote::class);
    }

    public function latestNote(): HasOne
    {
        return $this->hasOne(CfmMeetingNote::class)->latestOfMany();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'fap_review' => 'FAP Review',
            'licensing' => 'Licensing',
            'onboarding' => 'Onboarding',
            'promotion' => 'Promotion',
            'training' => 'Training',
            'coaching' => 'Coaching',
            default => 'General',
        };
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled'
            && $this->starts_at
            && $this->starts_at->isFuture();
    }
}
