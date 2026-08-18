<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistProgress extends Model
{
    protected $table = 'checklist_progress';

    protected $fillable = [
        'checklist_id',
        'user_id',
        'mentor_assignment_id',
        'status',
        'submitted_at',
        'completed_at',
        'completed_by',
        'approved_by',
        'approved_at',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'review_comments',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentorAssignment(): BelongsTo
    {
        return $this->belongsTo(MentorAssignment::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeMemberProgress(Builder $query): Builder
    {
        return $query->whereNull('mentor_assignment_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePendingConfirmation(Builder $query): Builder
    {
        return $query->where('status', 'pending_confirmation');
    }
}
