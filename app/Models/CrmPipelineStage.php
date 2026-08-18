<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipelineStage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'crm_pipeline_id',
        'name',
        'description',
        'slug',
        'color',
        'sort_order',
        'is_terminal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'crm_pipeline_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(CrmFunnelParticipation::class);
    }

    public function helpDescription(?CrmPipeline $pipeline = null): string
    {
        if (filled($this->description)) {
            return (string) $this->description;
        }

        $pipeline ??= $this->relationLoaded('pipeline') ? $this->pipeline : $this->pipeline()->first();
        $pipelineLabel = $pipeline?->short_name ?: ($pipeline?->name ?? 'this pipeline');

        if ($this->is_terminal) {
            return "Final {$pipelineLabel} outcome: {$this->name}. Keep the contact here unless they re-enter an active stage.";
        }

        return "{$this->name} is a stage in {$pipelineLabel}. Work the contact here, then advance when this milestone is complete.";
    }
}
