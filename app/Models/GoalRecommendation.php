<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalRecommendation extends Model
{
    protected $fillable = [
        'user_id',
        'goal_id',
        'recommendation_type',
        'priority',
        'message',
        'action_payload',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'action_payload' => 'array',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
