<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalScorecard extends Model
{
    protected $fillable = [
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'scores',
        'overall_score',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'scores' => 'array',
            'overall_score' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
