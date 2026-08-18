<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmPromotion extends Model
{
    public const STATUSES = ['tracking', 'ready', 'nominated'];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'current_rank_id',
        'target_rank_id',
        'readiness_percent',
        'requirements_met',
        'requirements_remaining',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'requirements_met' => 'array',
            'requirements_remaining' => 'array',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function currentRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'current_rank_id');
    }

    public function targetRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'target_rank_id');
    }
}
