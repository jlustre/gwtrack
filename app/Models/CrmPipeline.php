<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipeline extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'short_name',
        'description',
        'color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CrmPipelineStage::class)->orderBy('sort_order');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(CrmFunnelParticipation::class);
    }
}
