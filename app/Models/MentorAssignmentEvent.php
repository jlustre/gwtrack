<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorAssignmentEvent extends Model
{
    protected $fillable = [
        'mentor_assignment_id',
        'actor_id',
        'event_type',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MentorAssignment::class, 'mentor_assignment_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
