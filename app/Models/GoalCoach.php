<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalCoach extends Model
{
    protected $fillable = [
        'goal_id',
        'coach_user_id',
        'role',
        'can_edit',
        'receives_alerts',
    ];

    protected function casts(): array
    {
        return [
            'can_edit' => 'boolean',
            'receives_alerts' => 'boolean',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }
}
