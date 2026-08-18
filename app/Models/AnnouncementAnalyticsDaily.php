<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementAnalyticsDaily extends Model
{
    protected $table = 'announcement_analytics_daily';

    protected $fillable = [
        'stat_date',
        'announcement_id',
        'views',
        'reads',
        'acknowledgements',
        'reactions',
        'comments',
        'bookmarks',
        'reach',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'announcement_id' => 'integer',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(MessageCenterAnnouncement::class, 'announcement_id');
    }
}
