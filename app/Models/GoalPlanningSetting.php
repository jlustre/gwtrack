<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalPlanningSetting extends Model
{
    protected $fillable = [
        'user_id',
        'constants',
    ];

    protected function casts(): array
    {
        return [
            'constants' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
