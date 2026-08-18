<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaAssetDetail extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'emergency_fund' => 'decimal:2',
            'checking_savings' => 'decimal:2',
            'retirement_accounts' => 'decimal:2',
            'investment_accounts' => 'decimal:2',
            'real_estate_assets' => 'decimal:2',
            'business_assets' => 'decimal:2',
            'college_savings' => 'decimal:2',
            'other_assets' => 'decimal:2',
            'total_assets' => 'decimal:2',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
