<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmActionPlan extends Model
{
    public const STATUSES = ['active', 'completed', 'cancelled'];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'title',
        'summary',
        'steps',
        'status',
        'target_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'target_date' => 'date',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
