<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaDimeAnalysis extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'debt_inputs' => 'array',
            'total_debt' => 'decimal:2',
            'income_annual_to_replace' => 'decimal:2',
            'income_inflation_adjustment' => 'boolean',
            'existing_income_replacement_coverage' => 'decimal:2',
            'total_income_need' => 'decimal:2',
            'mortgage_balance' => 'decimal:2',
            'monthly_mortgage_payment' => 'decimal:2',
            'include_mortgage_payoff' => 'boolean',
            'total_mortgage_need' => 'decimal:2',
            'education_cost_per_child' => 'decimal:2',
            'education_inflation_adjustment' => 'boolean',
            'existing_education_savings' => 'decimal:2',
            'total_education_need' => 'decimal:2',
            'total_dime_need' => 'decimal:2',
            'existing_life_insurance' => 'decimal:2',
            'liquid_assets_allocated' => 'decimal:2',
            'estimated_protection_gap' => 'decimal:2',
            'recommended_coverage_min' => 'decimal:2',
            'recommended_coverage_max' => 'decimal:2',
            'calculated_at' => 'datetime',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
