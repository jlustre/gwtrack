<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskUserChecklistItem extends Model
{
    protected $fillable = [
        'task_user_id',
        'text',
        'is_done',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function taskUser(): BelongsTo
    {
        return $this->belongsTo(TaskUser::class);
    }
}
