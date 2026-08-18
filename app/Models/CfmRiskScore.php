<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmRiskScore extends Model
{
    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'score',
        'level',
        'flags',
        'recommended_actions',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'recommended_actions' => 'array',
            'assessed_at' => 'datetime',
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
}
