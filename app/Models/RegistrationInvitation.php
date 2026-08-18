<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RegistrationInvitation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sponsor_id',
        'accepted_by',
        'prospect_id',
        'code',
        'email',
        'role_name',
        'max_uses',
        'uses_count',
        'expires_at',
        'accepted_at',
        'last_emailed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'last_emailed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function invitationUrl(): string
    {
        return route('register.invitation', $this->code);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->notUsedForRegistration()
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeNotUsedForRegistration(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('accepted_by')
            ->whereColumn('uses_count', '<', 'max_uses');
    }

    public function scopeActiveForEmail(Builder $query, string $email): Builder
    {
        return $query
            ->active()
            ->where('email', strtolower($email));
    }

    public function scopePendingEmailFollowUp(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNull('last_emailed_at');
    }

    public function isAvailable(): bool
    {
        return $this->revoked_at === null
            && $this->accepted_at === null
            && $this->accepted_by === null
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && $this->uses_count < $this->max_uses
            && (bool) $this->sponsor?->is_active;
    }

    public function hasInviteeEmail(): bool
    {
        return filled($this->email);
    }

    public function matchesInviteeEmail(?string $email): bool
    {
        if (! $this->hasInviteeEmail()) {
            return true;
        }

        return strtolower(trim((string) $email)) === strtolower(trim((string) $this->email));
    }

    public function hasCompletedRegistration(): bool
    {
        return $this->accepted_at !== null
            || $this->accepted_by !== null
            || $this->uses_count >= $this->max_uses;
    }

    public function statusKey(): string
    {
        if ($this->accepted_at !== null) {
            return 'accepted';
        }

        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        if ($this->uses_count >= $this->max_uses) {
            return 'used';
        }

        return 'active';
    }

    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'active' => 'Active',
            'accepted' => 'Registered',
            'expired' => 'Expired',
            'revoked' => 'Deleted',
            'used' => 'Used',
            default => 'Closed',
        };
    }

    public function outcomeDescription(): string
    {
        return match ($this->statusKey()) {
            'active' => 'Waiting for registration',
            'accepted' => $this->acceptedBy
                ? 'Joined as '.$this->acceptedBy->name
                : 'Registration completed',
            'expired' => 'Link expired before it was used',
            'revoked' => 'Cancelled by you',
            'used' => 'All allowed uses were consumed',
            default => 'No longer available',
        };
    }

    public function recipientLabel(): string
    {
        if ($this->acceptedBy) {
            return $this->acceptedBy->name.' ('.$this->acceptedBy->email.')';
        }

        if (filled($this->email)) {
            return $this->email;
        }

        return 'Any email (open link)';
    }
}
