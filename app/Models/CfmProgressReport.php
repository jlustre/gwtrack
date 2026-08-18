<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmProgressReport extends Model
{
    public const TYPES = [
        'progress_snapshot',
        'coaching_summary',
        'promotion_readiness',
    ];

    public const AUDIENCES = [
        'cfm',
        'trainee',
        'leadership',
    ];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'report_type',
        'audience',
        'payload',
        'export_format',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function typeLabel(): string
    {
        return match ($this->report_type) {
            'coaching_summary' => 'Coaching Summary',
            'promotion_readiness' => 'Promotion Readiness',
            default => 'Progress Snapshot',
        };
    }
}
