<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmNote extends Model
{
    public const CATEGORIES = [
        'general',
        'strength',
        'weakness',
        'opportunity',
        'challenge',
        'recommendation',
    ];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'category',
        'body',
        'tags',
        'is_private',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_private' => 'boolean',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'strength' => 'Strength',
            'weakness' => 'Weakness',
            'opportunity' => 'Opportunity',
            'challenge' => 'Challenge',
            'recommendation' => 'Recommendation',
            default => 'General',
        };
    }
}
