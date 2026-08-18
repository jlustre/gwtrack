<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmTaskLog extends Model
{
    protected $fillable = [
        'cfm_task_id',
        'action',
        'details',
        'user_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(CfmTask::class, 'cfm_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
