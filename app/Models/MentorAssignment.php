<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MentorAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mentor_id',
        'apprentice_id',
        'assigned_by',
        'status',
        'started_at',
        'confirmed_at',
        'first_contact_sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'confirmed_at' => 'datetime',
            'first_contact_sent_at' => 'datetime',
            'completed_at' => 'date',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function apprentice(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprentice_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MentorNote::class);
    }

    public function traineeChecklistProgress(): HasMany
    {
        return $this->hasMany(ChecklistProgress::class, 'mentor_assignment_id');
    }
}
