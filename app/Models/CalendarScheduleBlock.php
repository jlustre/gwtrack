<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarScheduleBlock extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'work' => ['label' => 'Work', 'color' => '#64748B'],
        'personal' => ['label' => 'Personal', 'color' => '#7C3AED'],
        'other' => ['label' => 'Other', 'color' => '#94A3B8'],
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->block_type]['label'] ?? ucfirst($this->block_type);
    }

    public function typeColor(): string
    {
        return self::TYPES[$this->block_type]['color'] ?? '#94A3B8';
    }

    public function displayLabel(): string
    {
        return $this->label ?: $this->typeLabel();
    }

    /**
     * @param  array<int, string>  $weekdayLabels
     */
    public function weeklyRangeLabel(array $weekdayLabels): string
    {
        $endWeekday = $this->end_weekday ?? $this->weekday;

        if ((int) $endWeekday === (int) $this->weekday) {
            return ($weekdayLabels[$this->weekday] ?? 'Day '.$this->weekday)
                .' · '.substr((string) $this->starts_at, 0, 5)
                .' – '.substr((string) $this->ends_at, 0, 5);
        }

        return ($weekdayLabels[$this->weekday] ?? 'Day '.$this->weekday)
            .' '.substr((string) $this->starts_at, 0, 5)
            .' → '.($weekdayLabels[$endWeekday] ?? 'Day '.$endWeekday)
            .' '.substr((string) $this->ends_at, 0, 5);
    }
}
