<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalReview extends Model
{
    protected $fillable = [
        'goal_id',
        'reviewer_id',
        'review_type',
        'period_start',
        'period_end',
        'rating',
        'summary',
        'action_items',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rating' => 'integer',
            'action_items' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
