<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmLeaderboard extends Model
{
    protected $table = 'cfm_leaderboards';

    protected $fillable = [
        'metric_key',
        'period_start',
        'period_end',
        'cfm_id',
        'rank_position',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'score' => 'decimal:2',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }
}
