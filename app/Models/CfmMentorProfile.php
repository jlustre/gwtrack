<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmMentorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'certification_status',
        'hierarchy_access',
        'max_apprentices',
        'manual_unavailable',
        'share_calendar_with_apprentices',
        'share_calendar_with_agency_owner',
        'fap_completion_rate',
        'calendar_busyness_percent',
        'avg_apprentice_progress',
        'recommendation_score',
        'languages',
        'specialties',
        'licensed_jurisdictions',
        'mentor_bio',
        'last_mentor_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'specialties' => 'array',
            'licensed_jurisdictions' => 'array',
            'manual_unavailable' => 'boolean',
            'share_calendar_with_apprentices' => 'boolean',
            'share_calendar_with_agency_owner' => 'boolean',
            'fap_completion_rate' => 'decimal:2',
            'last_mentor_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
