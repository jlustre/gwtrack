<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FnaRecord extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'follow_up_date' => 'date',
            'dime_completed' => 'boolean',
            'is_client_portal' => 'boolean',
            'protection_gap' => 'decimal:2',
            'recommended_coverage_min' => 'decimal:2',
            'recommended_coverage_max' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'presented_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_user_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function household(): HasOne
    {
        return $this->hasOne(FnaHousehold::class);
    }

    public function incomeDetail(): HasOne
    {
        return $this->hasOne(FnaIncomeDetail::class);
    }

    public function debtDetail(): HasOne
    {
        return $this->hasOne(FnaDebtDetail::class);
    }

    public function assetDetail(): HasOne
    {
        return $this->hasOne(FnaAssetDetail::class);
    }

    public function existingCoverage(): HasOne
    {
        return $this->hasOne(FnaExistingCoverage::class);
    }

    public function goals(): HasOne
    {
        return $this->hasOne(FnaGoal::class);
    }

    public function riskAssessment(): HasOne
    {
        return $this->hasOne(FnaRiskAssessment::class);
    }

    public function dimeAnalysis(): HasOne
    {
        return $this->hasOne(FnaDimeAnalysis::class);
    }

    public function reviewComments(): HasMany
    {
        return $this->hasMany(FnaReviewComment::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(FnaStatusHistory::class)->latest('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FnaAttachment::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FnaPermission::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(FnaActivityLog::class)->latest('created_at');
    }

    public function clientInvites(): HasMany
    {
        return $this->hasMany(FnaClientInvite::class);
    }

    public function activeClientInvite(): HasOne
    {
        return $this->hasOne(FnaClientInvite::class)
            ->whereNotIn('status', ['revoked', 'expired'])
            ->latest('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskUser::class, 'related_fna_id');
    }

    public function statusLabel(): string
    {
        return config('fna.statuses')[$this->status] ?? str($this->status)->title()->toString();
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->status, ['draft', 'revision_requested', 'ready_for_review'], true);
    }
}
