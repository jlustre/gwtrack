<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRankProgress extends Model
{
    use SoftDeletes;

    protected $table = 'user_rank_progress';

    protected $fillable = [
        'user_id',
        'rank_requirement_id',
        'status',
        'member_notes',
        'reviewer_notes',
        'completed_at',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(RankRequirement::class, 'rank_requirement_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return config('rank-advancement.statuses.'.$this->status, ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, config('rank-advancement.completed_statuses', ['completed']), true);
    }
}
