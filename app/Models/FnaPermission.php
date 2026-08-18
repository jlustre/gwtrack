<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaPermission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'can_view_financial_details' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function scopeActiveFor(Builder $query, User $user): Builder
    {
        return $query
            ->where('shared_with_user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function allows(?string $flag): bool
    {
        if ($flag === 'can_view_financial_details') {
            return $this->can_view_financial_details;
        }

        if ($flag === null) {
            return true;
        }

        return in_array($this->permission_level, ['collaborate', 'review'], true);
    }
}
