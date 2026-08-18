<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementCampaignParticipant extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'progress_value',
        'progress_meta',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_value' => 'decimal:2',
            'progress_meta' => 'array',
            'joined_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AnnouncementCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
