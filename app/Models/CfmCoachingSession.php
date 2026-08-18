<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmCoachingSession extends Model
{
    public const FOCUS_AREAS = [
        'general',
        'onboarding',
        'fap',
        'licensing',
        'training',
        'goals',
        'promotion',
        'risk',
    ];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'focus_area',
        'notes',
        'strengths',
        'weaknesses',
        'recommendations',
        'session_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'weaknesses' => 'array',
            'recommendations' => 'array',
            'session_at' => 'datetime',
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
}
