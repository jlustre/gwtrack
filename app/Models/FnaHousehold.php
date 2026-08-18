<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaHousehold extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'children_details' => 'array',
            'financial_priorities' => 'array',
            'household_income' => 'decimal:2',
            'household_expenses' => 'decimal:2',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
