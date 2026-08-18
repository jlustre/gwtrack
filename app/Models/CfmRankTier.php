<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CfmRankTier extends Model
{
    protected $fillable = [
        'sort_order',
        'title',
        'icon',
        'criteria',
        'next_step',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
