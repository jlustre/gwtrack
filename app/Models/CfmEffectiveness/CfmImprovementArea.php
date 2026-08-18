<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmImprovementArea extends Model
{
    protected $fillable = [
        'cfm_id',
        'label',
        'source',
        'mention_count',
        'last_identified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_identified_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }
}
