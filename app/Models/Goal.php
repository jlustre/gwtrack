<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'goal_category_id',
        'parent_goal_id',
        'goal_template_id',
        'created_by_user_id',
        'hierarchy_level',
        'goal_type',
        'planning_type',
        'funnel_stage_key',
        'blueprint_id',
        'contribution_weight',
        'name',
        'description',
        'measurement_type',
        'metric_key',
        'target_value',
        'actual_value',
        'currency_code',
        'status',
        'smart_score',
        'smart_feedback',
        'starts_at',
        'deadline_at',
        'completed_at',
        'accountability_partner_id',
        'notification_settings',
        'streak_days',
        'current_streak',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'smart_score' => 'integer',
            'smart_feedback' => 'array',
            'contribution_weight' => 'decimal:2',
            'starts_at' => 'date',
            'deadline_at' => 'date',
            'completed_at' => 'datetime',
            'notification_settings' => 'array',
            'streak_days' => 'integer',
            'current_streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_goal_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_goal_id');
    }

    public function accountabilityPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountability_partner_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class)->orderBy('sort_order');
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(GoalProgress::class)->orderByDesc('recorded_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(GoalNote::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GoalComment::class)->whereNull('parent_id')->latest();
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(GoalCoach::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(GoalReview::class)->latest();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(GoalReminder::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(GoalForecast::class)->orderByDesc('forecast_date');
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(GoalBlueprint::class);
    }

    public function parentDependencies(): HasMany
    {
        return $this->hasMany(GoalDependency::class, 'parent_goal_id');
    }

    public function childDependencies(): HasMany
    {
        return $this->hasMany(GoalDependency::class, 'child_goal_id');
    }

    public function activityTargets(): HasMany
    {
        return $this->hasMany(GoalActivityTarget::class);
    }

    public function isActivityGoal(): bool
    {
        return $this->goal_type === 'activity';
    }

    public function isOutcomeGoal(): bool
    {
        return $this->goal_type === 'outcome' || $this->goal_type === null;
    }

    public function progressPercent(): int
    {
        if ($this->measurement_type === 'completion') {
            return $this->status === 'completed' ? 100 : (int) min(100, round((float) $this->actual_value));
        }

        $target = (float) $this->target_value;

        if ($target <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $this->actual_value / $target) * 100));
    }

    public function isOffTrack(): bool
    {
        if (! in_array($this->status, ['active', 'off_track'], true) || ! $this->deadline_at) {
            return false;
        }

        $start = ($this->starts_at ?? $this->created_at ?? now())->copy()->startOfDay();
        $deadline = $this->deadline_at->copy()->startOfDay();
        $today = now()->copy()->startOfDay();

        // Do not flag goals as off-track on (or before) their start day.
        if ($today->lte($start) || $deadline->lte($start)) {
            return false;
        }

        $totalDays = max(1, (int) $start->diffInDays($deadline));
        $elapsedDays = min($totalDays, (int) $start->diffInDays($today));
        $expectedProgress = min(100, (int) round(($elapsedDays / $totalDays) * 100));

        return $this->progressPercent() < ($expectedProgress * 0.8);
    }

    public function formattedActual(): string
    {
        return $this->formatValue((float) $this->actual_value);
    }

    public function formattedTarget(): string
    {
        return $this->formatValue((float) $this->target_value);
    }

    private function formatValue(float $value): string
    {
        return match ($this->measurement_type) {
            'currency' => '$'.number_format($value, 0),
            'percentage' => number_format($value, 0).'%',
            'completion' => number_format($value, 0).'%',
            default => number_format($value, 0),
        };
    }
}
