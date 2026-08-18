<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingLink extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_one_time' => 'boolean',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(BookingEventType::class, 'booking_event_type_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySchedule::class, 'availability_schedule_id');
    }

    public function apprentice(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprentice_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
