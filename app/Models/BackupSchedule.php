<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupSchedule extends Model
{
    protected $fillable = [
        'name',
        'backup_type',
        'frequency',
        'run_at',
        'days_of_week',
        'included_tables',
        'excluded_tables',
        'compressed',
        'encrypted',
        'retention_days',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'included_tables' => 'array',
            'excluded_tables' => 'array',
            'compressed' => 'boolean',
            'encrypted' => 'boolean',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
