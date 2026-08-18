<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmAoEvaluation extends Model
{
    protected $fillable = [
        'cfm_id',
        'evaluator_id',
        'period_start',
        'period_end',
        'status',
        'overall_score',
        'category_scores',
        'strengths',
        'improvement_areas',
        'recommendations',
        'promotion_potential',
        'leadership_potential',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'overall_score' => 'decimal:2',
            'category_scores' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
