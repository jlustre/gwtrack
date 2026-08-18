<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementReaction extends Model
{
    protected $fillable = [
        'announcement_id',
        'user_id',
        'reaction',
    ];

    protected function casts(): array
    {
        return [
            'announcement_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(MessageCenterAnnouncement::class, 'announcement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
