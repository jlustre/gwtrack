<?php

namespace App\Models\CfmEffectiveness;

use App\Models\MentorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmReview extends Model
{
    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'mentor_assignment_id',
        'review_cycle_id',
        'trigger_type',
        'status',
        'due_at',
        'submitted_at',
        'average_rating',
        'helped_most',
        'improvements',
        'comments',
        'suggestions',
        'analysis_summary',
        'requested_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'submitted_at' => 'datetime',
            'average_rating' => 'decimal:2',
            'analysis_summary' => 'array',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function mentorAssignment(): BelongsTo
    {
        return $this->belongsTo(MentorAssignment::class);
    }

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(CfmReviewCycle::class, 'review_cycle_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CfmFeedbackResponse::class, 'cfm_review_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
