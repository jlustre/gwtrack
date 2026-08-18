<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalForecast extends Model
{
    protected $fillable = [
        'goal_id',
        'forecast_date',
        'projected_value',
        'projected_percent',
        'confidence',
        'notes',
        'recommended_actions',
        'pace_status',
    ];

    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'projected_value' => 'decimal:2',
            'projected_percent' => 'decimal:2',
            'confidence' => 'integer',
            'recommended_actions' => 'array',
        ];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
