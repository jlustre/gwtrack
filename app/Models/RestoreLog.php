<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestoreLog extends Model
{
    protected $fillable = [
        'backup_id',
        'pre_restore_backup_id',
        'restore_type',
        'restore_mode',
        'status',
        'restored_by',
        'started_at',
        'completed_at',
        'error_message',
        'restored_tables',
        'metadata',
        'source_filename',
        'source_file_path',
    ];

    protected function casts(): array
    {
        return [
            'restored_tables' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function preRestoreBackup(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'pre_restore_backup_id');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestoreItem::class);
    }
}
