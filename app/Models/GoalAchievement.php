<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'goal_badge_id',
        'goal_id',
        'earned_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(GoalBadge::class, 'goal_badge_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
