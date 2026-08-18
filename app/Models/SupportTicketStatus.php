<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color_hex',
        'is_system_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'status_id');
    }
}
