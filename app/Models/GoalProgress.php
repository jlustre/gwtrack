<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgress extends Model
{
    protected $table = 'goal_progress';

    protected $fillable = [
        'goal_id',
        'recorded_at',
        'value',
        'source',
        'notes',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'value' => 'decimal:2',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
