<?php

namespace App\Models\CfmEffectiveness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmFeedbackQuestion extends Model
{
    protected $fillable = [
        'key',
        'question',
        'category',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CfmFeedbackResponse::class, 'question_id');
    }
}
