<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryLog extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'trigger_code',
        'channel',
        'status',
        'failure_reason',
        'provider_response',
        'attempted_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'provider_response' => 'array',
            'attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
