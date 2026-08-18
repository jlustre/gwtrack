<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalCoachingSession extends Model
{
    protected $fillable = [
        'coach_user_id',
        'trainee_user_id',
        'goal_id',
        'summary',
        'action_items',
        'session_at',
    ];

    protected function casts(): array
    {
        return [
            'action_items' => 'array',
            'session_at' => 'datetime',
        ];
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_user_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
