<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmFunnelStageHistory extends Model
{
    protected $fillable = [
        'crm_funnel_participation_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'reason',
        'notes',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(CrmFunnelParticipation::class, 'crm_funnel_participation_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'to_stage_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
