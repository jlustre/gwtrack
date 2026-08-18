<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Backup extends Model
{
    protected $fillable = [
        'backup_type',
        'filename',
        'file_path',
        'file_size',
        'status',
        'included_tables',
        'excluded_tables',
        'created_by',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
        'notes',
        'compressed',
        'encrypted',
        'disk',
    ];

    protected function casts(): array
    {
        return [
            'included_tables' => 'array',
            'excluded_tables' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'compressed' => 'boolean',
            'encrypted' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BackupItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restoreLogs(): HasMany
    {
        return $this->hasMany(RestoreLog::class);
    }
}
