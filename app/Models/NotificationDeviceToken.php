<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class NotificationDeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'subscription_payload',
        'platform',
        'device_name',
        'last_used_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Collection<int, self>
     */
    public static function activeForUser(int $userId): Collection
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->get();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
