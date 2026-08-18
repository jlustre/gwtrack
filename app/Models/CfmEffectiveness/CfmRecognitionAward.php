<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmRecognitionAward extends Model
{
    protected $fillable = [
        'cfm_id',
        'badge_id',
        'awarded_for_period',
        'awarded_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'awarded_for_period' => 'date',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(CfmRecognitionBadge::class, 'badge_id');
    }
}
