<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'icon',
        'color',
        'default_priority',
        'requires_acknowledgement_default',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgement_default' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
