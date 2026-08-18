<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledPhoneCall extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'person_type',
        'prospect_id',
        'related_user_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'scheduled_at',
        'timezone',
        'purpose',
        'notes',
        'status',
        'result',
        'result_notes',
        'rescheduled_to',
        'rescheduled_from_id',
        'calendar_event_id',
        'prospect_activity_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'rescheduled_to' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function prospectActivity(): BelongsTo
    {
        return $this->belongsTo(ProspectActivity::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function personTypeLabel(): string
    {
        return (string) (config('scheduled-phone-calls.person_types')[$this->person_type] ?? ucfirst(str_replace('_', ' ', $this->person_type)));
    }

    public function resultLabel(): ?string
    {
        if (! filled($this->result)) {
            return null;
        }

        return (string) (config('scheduled-phone-calls.results')[$this->result] ?? ucfirst($this->result));
    }

    public function isOpen(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->scheduled_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->isOpen() && ! $this->isOverdue();
    }
}
