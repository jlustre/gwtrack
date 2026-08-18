<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaIncomeDetail extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'other_income_sources' => 'array',
            'annual_income' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'spouse_annual_income' => 'decimal:2',
            'business_income' => 'decimal:2',
            'passive_income' => 'decimal:2',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
