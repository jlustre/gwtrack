<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarScheduleBlockOverride extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'block_date' => 'date',
            'block_end_date' => 'date',
            'is_all_day' => 'boolean',
            'is_blocked' => 'boolean',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return CalendarScheduleBlock::TYPES[$this->block_type]['label'] ?? ucfirst($this->block_type);
    }

    public function typeColor(): string
    {
        return CalendarScheduleBlock::TYPES[$this->block_type]['color'] ?? '#94A3B8';
    }

    public function displayLabel(): string
    {
        return $this->label ?: $this->typeLabel();
    }

    public function endDate(): \Carbon\CarbonInterface
    {
        return $this->block_end_date ?? $this->block_date;
    }

    public function spansMultipleDays(): bool
    {
        return ! $this->block_date->equalTo($this->endDate());
    }

    public function rangeLabel(): string
    {
        $start = $this->block_date;
        $end = $this->endDate();

        if ($this->is_all_day) {
            if ($start->equalTo($end)) {
                return $start->format('M j, Y').' · All day';
            }

            return $start->format('M j').' – '.$end->format('M j, Y').' · All day';
        }

        $startTime = $this->starts_at ? substr((string) $this->starts_at, 0, 5) : null;
        $endTime = $this->ends_at ? substr((string) $this->ends_at, 0, 5) : null;

        if ($start->equalTo($end)) {
            return $start->format('M j, Y').' · '.$startTime.' – '.$endTime;
        }

        return $start->format('M j').', '.$startTime.' – '.$end->format('M j').', '.$endTime;
    }
}
