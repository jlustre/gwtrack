<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationEscalationRule extends Model
{
    protected $fillable = [
        'code',
        'name',
        'module',
        'condition_type',
        'condition_config',
        'escalation_steps',
        'cooldown_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'condition_config' => 'array',
            'escalation_steps' => 'array',
            'cooldown_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationEscalationLog::class);
    }
}
