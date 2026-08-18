<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmScorecard extends Model
{
    protected $fillable = [
        'cfm_id',
        'ao_evaluation_id',
        'period_type',
        'period_start',
        'period_end',
        'categories',
        'overall_score',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'categories' => 'array',
            'overall_score' => 'decimal:2',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function aoEvaluation(): BelongsTo
    {
        return $this->belongsTo(CfmAoEvaluation::class, 'ao_evaluation_id');
    }
}
