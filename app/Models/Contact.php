<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sourceProspect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'source_prospect_id');
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    public function salesOpportunities(): HasMany
    {
        return $this->hasMany(SalesOpportunity::class);
    }

    public function recruitingOpportunities(): HasMany
    {
        return $this->hasMany(RecruitingOpportunity::class);
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(ContactReferral::class, 'referrer_contact_id');
    }

    public function referralsReceived(): HasMany
    {
        return $this->hasMany(ContactReferral::class, 'referred_contact_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'related_contact_id');
    }

    public function displayName(): string
    {
        $preferred = trim((string) ($this->preferred_name ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        $fullName = trim(trim((string) ($this->first_name ?? '')).' '.trim((string) ($this->last_name ?? '')));

        return $fullName !== '' ? $fullName : 'Unnamed Contact';
    }
}
