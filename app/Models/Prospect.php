<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospect extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'spouse_date_of_birth' => 'date',
            'next_follow_up_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'appointment_at' => 'datetime',
            'conversion_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'is_client' => 'boolean',
            'is_archived' => 'boolean',
            'engagement_score' => 'decimal:2',
            'dependents' => 'array',
            'qualification_traits' => 'array',
        ];
    }

    public function funnel(): BelongsTo
    {
        return $this->belongsTo(ProspectFunnel::class, 'prospect_funnel_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function salesOpportunity(): BelongsTo
    {
        return $this->belongsTo(SalesOpportunity::class);
    }

    public function recruitingOpportunity(): BelongsTo
    {
        return $this->belongsTo(RecruitingOpportunity::class);
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(ProspectStageHistory::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProspectActivity::class);
    }

    public function displayName(): string
    {
        $preferred = trim((string) ($this->preferred_name ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        $fullName = trim(trim((string) ($this->first_name ?? '')).' '.trim((string) ($this->last_name ?? '')));

        return $fullName !== '' ? $fullName : 'Unnamed Prospect';
    }

    public function pipelineLabel(): string
    {
        return (string) config('prospects.pipeline_labels.'.$this->funnel_type, 'Sales');
    }

    /**
     * @param  Builder<Prospect>  $query
     * @return Builder<Prospect>
     */
    public function scopeForSalesPipeline(Builder $query): Builder
    {
        return $query->whereIn('funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']));
    }

    /**
     * @param  Builder<Prospect>  $query
     * @return Builder<Prospect>
     */
    public function scopeForRecruitingPipeline(Builder $query): Builder
    {
        return $query->whereIn('funnel_type', config('prospects.recruiting_funnel_types', ['recruiting', 'both']));
    }

    public function fullName(): string
    {
        return trim(trim((string) ($this->first_name ?? '')).' '.trim((string) ($this->last_name ?? '')));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ProspectSource::class, 'prospect_source_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(ProspectType::class, 'prospect_type_prospect')->withTimestamps();
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(ProspectInterest::class, 'prospect_interest_prospect')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProspectTag::class, 'prospect_tag_pivot')->withTimestamps();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProspectNote::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ProspectCommunication::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ProspectAppointment::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'related_prospect_id');
    }

    public function fnaRecords(): HasMany
    {
        return $this->hasMany(FnaRecord::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(ProspectFollowUp::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProspectFile::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProspectShare::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ProspectAccessLog::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ProspectConversion::class);
    }

    public function registrationInvitations(): HasMany
    {
        return $this->hasMany(RegistrationInvitation::class);
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(ContactReferral::class, 'referrer_prospect_id');
    }

    public function referralsReceived(): HasMany
    {
        return $this->hasMany(ContactReferral::class, 'referred_prospect_id');
    }
}
