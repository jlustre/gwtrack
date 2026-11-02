<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FnaClientInvite extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'last_saved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_emailed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function recipientTypeLabel(): string
    {
        if ($this->recipient_user_id) {
            return 'GWTracks member';
        }

        if ($this->prospect_id) {
            return 'Prospect';
        }

        return 'External client';
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function inviteUrl(): string
    {
        return route('fna.client.invite', $this->token);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ! in_array($this->status, ['expired', 'revoked', 'submitted'], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function statusLabel(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }
}
