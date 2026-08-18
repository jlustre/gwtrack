<?php

namespace App\Models\CfmEffectiveness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmFeedbackResponse extends Model
{
    protected $fillable = [
        'cfm_review_id',
        'question_id',
        'rating',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(CfmReview::class, 'cfm_review_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CfmFeedbackQuestion::class, 'question_id');
    }
}
