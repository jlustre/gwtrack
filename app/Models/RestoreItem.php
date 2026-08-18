<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestoreItem extends Model
{
    protected $fillable = [
        'restore_log_id',
        'table_name',
        'records_before',
        'records_restored',
        'records_skipped',
        'status',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function restoreLog(): BelongsTo
    {
        return $this->belongsTo(RestoreLog::class);
    }
}
