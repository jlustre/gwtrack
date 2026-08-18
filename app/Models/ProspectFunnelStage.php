<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectFunnelStage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_terminal' => 'boolean',
            'auto_task_template' => 'array',
            'conversion_weight' => 'decimal:2',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(ProspectFunnel::class, 'prospect_funnel_id');
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }
}
