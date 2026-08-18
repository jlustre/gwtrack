<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarEventType::class, 'calendar_event_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CalendarCategory::class, 'calendar_category_id');
    }

    public function getDisplayColorAttribute(): string
    {
        return $this->category?->color ?? $this->color ?? '#84BB41';
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function relatedProspect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'related_prospect_id');
    }

    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }

    public function relatedSalesOpportunity(): BelongsTo
    {
        return $this->belongsTo(SalesOpportunity::class, 'related_sales_opportunity_id');
    }

    public function relatedRecruitingOpportunity(): BelongsTo
    {
        return $this->belongsTo(RecruitingOpportunity::class, 'related_recruiting_opportunity_id');
    }

    public function relatedFna(): BelongsTo
    {
        return $this->belongsTo(FnaRecord::class, 'related_fna_id');
    }

    public function relatedApprentice(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_apprentice_id');
    }

    public function relatedTrainingModule(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'related_training_module_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(CalendarEventAttendee::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CalendarEventReminder::class);
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(CalendarEventRecurrence::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CalendarEventAttachment::class);
    }

    public function visibilityRules(): HasMany
    {
        return $this->hasMany(CalendarEventVisibilityRule::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CalendarEventNote::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CalendarEventActivityLog::class);
    }
}
