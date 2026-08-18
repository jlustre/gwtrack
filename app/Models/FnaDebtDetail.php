<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaDebtDetail extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mortgage_balance' => 'decimal:2',
            'rent_amount' => 'decimal:2',
            'credit_card_debt' => 'decimal:2',
            'car_loans' => 'decimal:2',
            'student_loans' => 'decimal:2',
            'personal_loans' => 'decimal:2',
            'business_debt' => 'decimal:2',
            'other_liabilities' => 'decimal:2',
            'total_debt' => 'decimal:2',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
