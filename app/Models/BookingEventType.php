<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingEventType extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'approval_required' => 'boolean',
            'is_active' => 'boolean',
            'custom_questions_enabled' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function calendarCategory(): BelongsTo
    {
        return $this->belongsTo(CalendarCategory::class);
    }

    public function linkedChecklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'linked_checklist_id');
    }

    public function linkedTrainingModule(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'linked_training_module_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(BookingLink::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(BookingQuestion::class);
    }
}
