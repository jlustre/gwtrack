<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmRecommendationSuggestion extends Model
{
    protected $fillable = [
        'recommendation_type',
        'cfm_user_id',
        'label',
        'cfm_name',
        'fit_score',
        'status_label',
        'detail',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_user_id');
    }
}
