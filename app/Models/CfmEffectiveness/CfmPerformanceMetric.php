<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmPerformanceMetric extends Model
{
    protected $fillable = [
        'cfm_id',
        'metric_key',
        'value',
        'score',
        'period_start',
        'period_end',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'score' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'meta' => 'array',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }
}
