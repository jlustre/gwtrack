<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreferenceDefault extends Model
{
    protected $fillable = [
        'role',
        'notification_type_id',
        'notification_channel_id',
        'enabled',
        'frequency',
    ];

    protected function casts(): array
    {
        return [
            'notification_type_id' => 'integer',
            'notification_channel_id' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'notification_channel_id');
    }
}
