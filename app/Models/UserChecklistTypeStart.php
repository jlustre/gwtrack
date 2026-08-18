<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChecklistTypeStart extends Model
{
    protected $fillable = [
        'user_id',
        'checklist_type_id',
        'started_at',
        'started_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checklistType(): BelongsTo
    {
        return $this->belongsTo(ChecklistType::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}
