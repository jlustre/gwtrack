<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSlaPolicy extends Model
{
    protected $fillable = [
        'urgency',
        'response_time_hours',
    ];

    protected function casts(): array
    {
        return [
            'response_time_hours' => 'integer',
        ];
    }
}
