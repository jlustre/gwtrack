<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'group',
        'sort_order',
        'is_active',
        'user_configurable',
        'digest_eligible',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'user_configurable' => 'boolean',
            'digest_eligible' => 'boolean',
        ];
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(NotificationTrigger::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
