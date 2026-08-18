<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnouncementCampaign extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'type',
        'description',
        'rules',
        'prizes',
        'starts_at',
        'ends_at',
        'is_active',
        'leaderboard_metric',
        'leaderboard_config',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'prizes' => 'array',
            'leaderboard_config' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AnnouncementCampaignParticipant::class, 'campaign_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(MessageCenterAnnouncement::class, 'campaign_id');
    }

    public function isRunning(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }
}
