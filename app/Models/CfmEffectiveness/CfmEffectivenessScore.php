<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmEffectivenessScore extends Model
{
    protected $fillable = [
        'cfm_id',
        'period_type',
        'period_start',
        'period_end',
        'objective_score',
        'feedback_score',
        'ao_score',
        'overall_score',
        'weights',
        'metrics_snapshot',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'objective_score' => 'decimal:2',
            'feedback_score' => 'decimal:2',
            'ao_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'weights' => 'array',
            'metrics_snapshot' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }
}
