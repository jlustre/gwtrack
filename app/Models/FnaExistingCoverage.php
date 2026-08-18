<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnaExistingCoverage extends Model
{
    protected $table = 'fna_existing_coverages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'existing_life_insurance_amount' => 'decimal:2',
            'term_coverage' => 'decimal:2',
            'whole_life_coverage' => 'decimal:2',
            'universal_life_coverage' => 'decimal:2',
            'group_insurance_coverage' => 'decimal:2',
            'disability_coverage' => 'decimal:2',
            'critical_illness_coverage' => 'decimal:2',
            'long_term_care_coverage' => 'decimal:2',
            'policy_review_needed' => 'boolean',
        ];
    }

    public function fnaRecord(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class);
    }
}
