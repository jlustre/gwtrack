<?php

namespace App\Models\CfmEffectiveness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmReviewCycle extends Model
{
    protected $fillable = [
        'code',
        'name',
        'trigger_type',
        'days_after_assignment',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CfmReview::class, 'review_cycle_id');
    }
}
