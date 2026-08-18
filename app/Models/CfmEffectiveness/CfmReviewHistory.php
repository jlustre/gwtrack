<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CfmReviewHistory extends Model
{
    protected $table = 'cfm_review_histories';

    protected $fillable = [
        'cfm_id',
        'review_type',
        'score',
        'comments',
        'status',
        'reviewer_id',
        'reviewable_type',
        'reviewable_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
