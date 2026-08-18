<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalDependency extends Model
{
    protected $fillable = [
        'parent_goal_id',
        'child_goal_id',
        'relationship_type',
        'contribution_percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'contribution_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function parentGoal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'parent_goal_id');
    }

    public function childGoal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'child_goal_id');
    }
}
