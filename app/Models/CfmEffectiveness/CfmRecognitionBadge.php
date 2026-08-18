<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmRecognitionBadge extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'criteria_key',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CfmRecognitionAward::class, 'badge_id');
    }
}
