<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalBadge extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'level',
        'criteria',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(GoalAchievement::class);
    }
}
