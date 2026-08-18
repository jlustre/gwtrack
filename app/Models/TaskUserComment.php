<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskUserComment extends Model
{
    protected $fillable = [
        'task_user_id',
        'user_id',
        'body',
    ];

    public function taskUser(): BelongsTo
    {
        return $this->belongsTo(TaskUser::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
