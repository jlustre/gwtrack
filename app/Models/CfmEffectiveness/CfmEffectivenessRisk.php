<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmEffectivenessRisk extends Model
{
    protected $fillable = [
        'cfm_id',
        'risk_type',
        'severity',
        'message',
        'meta',
        'detected_at',
        'resolved_at',
        'ao_notified',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'ao_notified' => 'boolean',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }
}
