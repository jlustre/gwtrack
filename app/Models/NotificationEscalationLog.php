<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationEscalationLog extends Model
{
    protected $fillable = [
        'escalation_rule_id',
        'subject_type',
        'subject_id',
        'step_index',
        'notified_user_ids',
        'trigger_code',
        'fired_at',
    ];

    protected function casts(): array
    {
        return [
            'step_index' => 'integer',
            'notified_user_ids' => 'array',
            'fired_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationEscalationRule::class, 'escalation_rule_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
