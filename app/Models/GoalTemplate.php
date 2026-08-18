<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalTemplate extends Model
{
    protected $fillable = [
        'goal_category_id',
        'name',
        'description',
        'hierarchy_level',
        'measurement_type',
        'metric_key',
        'default_target',
        'suggested_milestones',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_target' => 'decimal:2',
            'suggested_milestones' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }
}
