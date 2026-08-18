<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDigestSetting extends Model
{
    protected $fillable = [
        'user_id',
        'digest_type',
        'send_at',
        'send_day',
        'timezone_id',
        'enabled',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'send_day' => 'integer',
            'enabled' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timezoneRecord(): BelongsTo
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }
}
