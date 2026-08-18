<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTrigger extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'notification_type_id',
        'code',
        'name',
        'description',
        'event_key',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'notification_type_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'trigger_id');
    }
}
