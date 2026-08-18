<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProspectActivity extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'call' => 'Phone Call',
        'email' => 'Email',
        'text' => 'Text Message',
        'meeting' => 'Meeting',
        'visit' => 'In-Person Visit',
        'social' => 'Social Media',
        'other' => 'Other',
    ];

    /** @return array<string, string> */
    public static function activityTypes(): array
    {
        return config('prospects.activity_types', self::TYPES);
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
