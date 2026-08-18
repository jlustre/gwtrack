<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmEffectivenessActionPlan extends Model
{
    protected $table = 'cfm_effectiveness_action_plans';

    protected $fillable = [
        'cfm_id',
        'improvement_area',
        'target_outcome',
        'action_steps',
        'due_date',
        'progress',
        'status',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'action_steps' => 'array',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
