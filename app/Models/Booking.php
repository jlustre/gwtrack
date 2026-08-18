<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(BookingEventType::class, 'booking_event_type_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(BookingLink::class, 'booking_link_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySchedule::class, 'availability_schedule_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function relatedProspect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'related_prospect_id');
    }

    public function relatedChecklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'related_checklist_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(BookingAttendee::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(BookingAnswer::class);
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(BookingReschedule::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(BookingCancellation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(BookingActivityLog::class);
    }
}
