<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProductionEntry extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'policy_reference',
        'description',
        'annual_premium',
        'status',
        'posted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'annual_premium' => 'decimal:2',
            'posted_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
