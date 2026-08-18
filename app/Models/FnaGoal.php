<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaGoal extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'selected_goals' => 'array',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
