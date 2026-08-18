<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalConversionRate extends Model
{
    protected $fillable = [
        'user_id',
        'funnel_key',
        'from_stage',
        'to_stage',
        'rate',
        'sample_size',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'sample_size' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
