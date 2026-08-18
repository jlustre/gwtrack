<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmFunnelParticipation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'crm_pipeline_id',
        'crm_pipeline_stage_id',
        'owner_id',
        'status',
        'state',
        'progress_percent',
        'priority',
        'probability',
        'entered_at',
        'stage_entered_at',
        'expected_completion_at',
        'completed_at',
        'outcome',
        'inactive_reason',
        'last_activity_at',
        'next_action',
        'next_action_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'date',
            'stage_entered_at' => 'date',
            'expected_completion_at' => 'date',
            'completed_at' => 'date',
            'next_action_at' => 'date',
            'last_activity_at' => 'datetime',
            'probability' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'crm_pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'crm_pipeline_stage_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(CrmFunnelStageHistory::class);
    }
}
