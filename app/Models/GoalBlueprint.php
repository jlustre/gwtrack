<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalBlueprint extends Model
{
    protected $fillable = [
        'user_id',
        'planning_type',
        'name',
        'period_type',
        'root_target_value',
        'status',
        'funnel_snapshot',
        'conversion_snapshot',
        'starts_at',
        'deadline_at',
        'root_goal_id',
    ];

    protected function casts(): array
    {
        return [
            'root_target_value' => 'decimal:2',
            'funnel_snapshot' => 'array',
            'conversion_snapshot' => 'array',
            'starts_at' => 'date',
            'deadline_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rootGoal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'root_goal_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'blueprint_id');
    }
}
