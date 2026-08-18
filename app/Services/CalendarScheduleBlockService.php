<?php

namespace App\Services;

use App\Models\CalendarScheduleBlock;
use App\Models\CalendarScheduleBlockOverride;
use App\Models\MentorAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalendarScheduleBlockService
{
    public function __construct(
        private readonly CalendarShareService $calendarShare,
    ) {}

    /**
     * @return Collection<int, CalendarScheduleBlock>
     */
    public function weeklyBlocksFor(User $user): Collection
    {
        return CalendarScheduleBlock::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, CalendarScheduleBlockOverride>
     */
    public function overridesFor(User $user): Collection
    {
        $cutoff = now()->subMonth()->toDateString();

        return CalendarScheduleBlockOverride::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($cutoff): void {
                $query->where('block_date', '>=', $cutoff)
                    ->orWhere('block_end_date', '>=', $cutoff);
            })
            ->orderBy('block_date')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function expandedBlocksByDate(User $user, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $weekly = CalendarScheduleBlock::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $overrides = CalendarScheduleBlockOverride::query()
            ->where('user_id', $user->id)
            ->where('block_date', '<=', $rangeEnd->toDateString())
            ->whereRaw('COALESCE(block_end_date, block_date) >= ?', [$rangeStart->toDateString()])
            ->get();

        $byDate = [];

        for ($day = $rangeStart; $day->lte($rangeEnd); $day = $day->addDay()) {
            $dateKey = $day->toDateString();
            $items = collect();

            foreach ($weekly as $block) {
                $occurrence = $this->formatWeeklyOccurrenceForDay($block, $day, $user);

                if ($occurrence !== null) {
                    $items->push($occurrence);
                }
            }

            foreach ($overrides as $override) {
                $occurrence = $this->formatOverrideOccurrenceForDay($override, $day, $user);

                if ($occurrence === null) {
                    continue;
                }

                if ($override->is_blocked) {
                    $items->push($occurrence);
                } else {
                    $items = $items->reject(function (array $item) use ($occurrence): bool {
                        if ($occurrence['is_all_day']) {
                            return true;
                        }

                        return $this->timesOverlap(
                            $item['starts_at']->format('H:i:s'),
                            $item['ends_at']->format('H:i:s'),
                            $occurrence['starts_at']->format('H:i:s'),
                            $occurrence['ends_at']->format('H:i:s'),
                        );
                    });
                }
            }

            if ($items->isNotEmpty()) {
                $byDate[$dateKey] = $items->sortBy('starts_at')->values();
            }
        }

        return $byDate;
    }

    /**
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function sharedExpandedBlocksByDate(User $viewer, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $owners = $this->calendarShare->sharedScheduleBlockOwnersFor($viewer);

        $merged = [];

        foreach ($owners as $owner) {
            foreach ($this->expandedBlocksByDate($owner, $rangeStart, $rangeEnd) as $date => $items) {
                $sharedItems = $items
                    ->filter(fn (array $item): bool => (bool) ($item['is_shared'] ?? true))
                    ->map(function (array $item) use ($owner): array {
                        $item['owner_id'] = $owner->id;
                        $item['owner_name'] = $owner->name;

                        return $item;
                    });

                if ($sharedItems->isEmpty()) {
                    continue;
                }

                $merged[$date] = ($merged[$date] ?? collect())->merge($sharedItems)->sortBy('starts_at')->values();
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, Collection<int, array<string, mixed>>>  $ownBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $sharedBlocksByDate
     * @return Collection<int, array<string, mixed>>
     */
    public function agendaBlocksFromExpanded(
        array $ownBlocksByDate,
        array $sharedBlocksByDate,
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd,
    ): Collection {
        $items = collect();

        for ($day = $rangeStart; $day->lte($rangeEnd); $day = $day->addDay()) {
            $dateKey = $day->toDateString();
            $dayBlocks = collect($ownBlocksByDate[$dateKey] ?? [])
                ->merge(collect($sharedBlocksByDate[$dateKey] ?? []));

            foreach ($dayBlocks as $block) {
                if (in_array($block['span_position'] ?? 'single', ['start', 'single'], true)) {
                    $items->push($block);
                }
            }
        }

        return $items
            ->unique(fn (array $block): string => (string) ($block['range_key'] ?? $block['id']))
            ->sortBy(fn (array $block) => $block['range_starts_at'])
            ->values();
    }

    public function storeWeeklyBlock(User $user, array $data): CalendarScheduleBlock
    {
        return CalendarScheduleBlock::create([
            'user_id' => $user->id,
            'block_type' => $data['block_type'],
            'label' => $data['label'] ?? null,
            'weekday' => (int) $data['weekday'],
            'end_weekday' => filled($data['end_weekday'] ?? null) ? (int) $data['end_weekday'] : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_shared' => (bool) ($data['is_shared'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function updateWeeklyBlock(CalendarScheduleBlock $block, array $data): CalendarScheduleBlock
    {
        $block->update([
            'block_type' => $data['block_type'],
            'label' => $data['label'] ?? null,
            'weekday' => (int) $data['weekday'],
            'end_weekday' => filled($data['end_weekday'] ?? null) ? (int) $data['end_weekday'] : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_shared' => (bool) ($data['is_shared'] ?? true),
        ]);

        return $block->refresh();
    }

    public function storeOverride(User $user, array $data): CalendarScheduleBlockOverride
    {
        $isAllDay = (bool) ($data['is_all_day'] ?? false);

        return CalendarScheduleBlockOverride::create([
            'user_id' => $user->id,
            'block_date' => $data['block_date'],
            'block_end_date' => $data['block_end_date'] ?? null,
            'starts_at' => $isAllDay ? null : ($data['starts_at'] ?? null),
            'ends_at' => $isAllDay ? null : ($data['ends_at'] ?? null),
            'is_all_day' => $isAllDay,
            'block_type' => $data['block_type'] ?? 'other',
            'label' => $data['label'] ?? null,
            'reason' => $data['reason'] ?? null,
            'is_blocked' => (bool) ($data['is_blocked'] ?? true),
            'is_shared' => (bool) ($data['is_shared'] ?? true),
        ]);
    }

    public function updateOverride(CalendarScheduleBlockOverride $override, array $data): CalendarScheduleBlockOverride
    {
        $isAllDay = (bool) ($data['is_all_day'] ?? false);

        $override->update([
            'block_date' => $data['block_date'],
            'block_end_date' => $data['block_end_date'] ?? null,
            'starts_at' => $isAllDay ? null : ($data['starts_at'] ?? null),
            'ends_at' => $isAllDay ? null : ($data['ends_at'] ?? null),
            'is_all_day' => $isAllDay,
            'block_type' => $data['block_type'] ?? 'other',
            'label' => $data['label'] ?? null,
            'reason' => $data['reason'] ?? null,
            'is_shared' => (bool) ($data['is_shared'] ?? true),
        ]);

        return $override->refresh();
    }

    /**
     * @return list<int>
     */
    public function apprenticeIdsForCfm(User $cfm): array
    {
        return MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->pluck('apprentice_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatWeeklyOccurrenceForDay(CalendarScheduleBlock $block, CarbonImmutable $day, User $user): ?array
    {
        $bounds = $this->weeklyOccurrenceBoundsForDay($block, $day);

        if ($bounds === null) {
            return null;
        }

        $rangeStartsAt = $bounds['starts_at'];
        $rangeEndsAt = $bounds['ends_at'];
        $startDate = $rangeStartsAt->startOfDay();
        $endDate = $rangeEndsAt->startOfDay();
        $dayStart = $day->startOfDay();

        if ($startDate->equalTo($endDate)) {
            $startsAt = $day->setTimeFromTimeString(substr((string) $block->starts_at, 0, 8));
            $endsAt = $day->setTimeFromTimeString(substr((string) $block->ends_at, 0, 8));
            $spanPosition = 'single';
        } elseif ($dayStart->equalTo($startDate)) {
            $startsAt = $day->setTimeFromTimeString(substr((string) $block->starts_at, 0, 8));
            $endsAt = $day->endOfDay();
            $spanPosition = 'start';
        } elseif ($dayStart->equalTo($endDate)) {
            $startsAt = $day->startOfDay();
            $endsAt = $day->setTimeFromTimeString(substr((string) $block->ends_at, 0, 8));
            $spanPosition = 'end';
        } else {
            $startsAt = $day->startOfDay();
            $endsAt = $day->endOfDay();
            $spanPosition = 'middle';
        }

        return $this->buildBlockPayload(
            id: 'block-'.$block->id.'-'.$day->toDateString(),
            source: 'weekly',
            blockType: $block->block_type,
            label: $block->displayLabel(),
            color: $block->typeColor(),
            startsAt: $startsAt,
            endsAt: $endsAt,
            isAllDay: $spanPosition === 'middle',
            isShared: (bool) $block->is_shared,
            user: $user,
            rangeKey: 'weekly-'.$block->id.'-'.$startDate->toDateString(),
            rangeStartsAt: $rangeStartsAt,
            rangeEndsAt: $rangeEndsAt,
            spanPosition: $spanPosition,
            rangeIsAllDay: false,
        );
    }

    /**
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}|null
     */
    private function weeklyOccurrenceBoundsForDay(CalendarScheduleBlock $block, CarbonImmutable $day): ?array
    {
        $startWeekday = (int) $block->weekday;
        $endWeekday = (int) ($block->end_weekday ?? $block->weekday);
        $dayStart = $day->startOfDay();
        $weekStart = $day->startOfWeek();

        if ($startWeekday === $endWeekday) {
            $rangeStartDay = $weekStart->addDays($startWeekday - 1);

            if (! $dayStart->equalTo($rangeStartDay)) {
                return null;
            }

            return [
                'starts_at' => $rangeStartDay->setTimeFromTimeString(substr((string) $block->starts_at, 0, 8)),
                'ends_at' => $rangeStartDay->setTimeFromTimeString(substr((string) $block->ends_at, 0, 8)),
            ];
        }

        if ($endWeekday > $startWeekday) {
            $rangeStartDay = $weekStart->addDays($startWeekday - 1);
            $rangeEndDay = $weekStart->addDays($endWeekday - 1);
        } elseif ($day->dayOfWeekIso <= $endWeekday) {
            $rangeStartDay = $weekStart->subWeek()->addDays($startWeekday - 1);
            $rangeEndDay = $weekStart->addDays($endWeekday - 1);
        } else {
            $rangeStartDay = $weekStart->addDays($startWeekday - 1);
            $rangeEndDay = $weekStart->addWeek()->addDays($endWeekday - 1);
        }

        if ($dayStart->lt($rangeStartDay) || $dayStart->gt($rangeEndDay)) {
            return null;
        }

        return [
            'starts_at' => $rangeStartDay->setTimeFromTimeString(substr((string) $block->starts_at, 0, 8)),
            'ends_at' => $rangeEndDay->setTimeFromTimeString(substr((string) $block->ends_at, 0, 8)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatOverrideOccurrenceForDay(CalendarScheduleBlockOverride $override, CarbonImmutable $day, User $user): ?array
    {
        $startDate = CarbonImmutable::parse($override->block_date->toDateString())->startOfDay();
        $endDate = CarbonImmutable::parse($override->endDate()->toDateString())->startOfDay();

        if ($day->lt($startDate) || $day->gt($endDate)) {
            return null;
        }

        $rangeStartsAt = $override->is_all_day
            ? $startDate->startOfDay()
            : $startDate->setTimeFromTimeString(substr((string) $override->starts_at, 0, 8));

        $rangeEndsAt = $override->is_all_day
            ? $endDate->endOfDay()
            : $endDate->setTimeFromTimeString(substr((string) $override->ends_at, 0, 8));

        if ($override->is_all_day) {
            $spanPosition = match (true) {
                $startDate->equalTo($endDate) => 'single',
                $day->equalTo($startDate) => 'start',
                $day->equalTo($endDate) => 'end',
                default => 'middle',
            };

            return $this->buildBlockPayload(
                id: 'override-'.$override->id.'-'.$day->toDateString(),
                source: 'override',
                blockType: $override->block_type,
                label: $override->displayLabel(),
                color: $override->typeColor(),
                startsAt: $day->startOfDay(),
                endsAt: $day->endOfDay(),
                isAllDay: true,
                isShared: (bool) $override->is_shared,
                user: $user,
                rangeKey: 'override-'.$override->id,
                rangeStartsAt: $rangeStartsAt,
                rangeEndsAt: $rangeEndsAt,
                spanPosition: $spanPosition,
                rangeIsAllDay: true,
            );
        }

        if ($startDate->equalTo($endDate)) {
            $startsAt = $day->setTimeFromTimeString(substr((string) $override->starts_at, 0, 8));
            $endsAt = $day->setTimeFromTimeString(substr((string) $override->ends_at, 0, 8));
            $spanPosition = 'single';
        } elseif ($day->equalTo($startDate)) {
            $startsAt = $day->setTimeFromTimeString(substr((string) $override->starts_at, 0, 8));
            $endsAt = $day->endOfDay();
            $spanPosition = 'start';
        } elseif ($day->equalTo($endDate)) {
            $startsAt = $day->startOfDay();
            $endsAt = $day->setTimeFromTimeString(substr((string) $override->ends_at, 0, 8));
            $spanPosition = 'end';
        } else {
            $startsAt = $day->startOfDay();
            $endsAt = $day->endOfDay();
            $spanPosition = 'middle';
        }

        return $this->buildBlockPayload(
            id: 'override-'.$override->id.'-'.$day->toDateString(),
            source: 'override',
            blockType: $override->block_type,
            label: $override->displayLabel(),
            color: $override->typeColor(),
            startsAt: $startsAt,
            endsAt: $endsAt,
            isAllDay: false,
            isShared: (bool) $override->is_shared,
            user: $user,
            rangeKey: 'override-'.$override->id,
            rangeStartsAt: $rangeStartsAt,
            rangeEndsAt: $rangeEndsAt,
            spanPosition: $spanPosition,
            rangeIsAllDay: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBlockPayload(
        string $id,
        string $source,
        string $blockType,
        string $label,
        string $color,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        bool $isAllDay,
        bool $isShared,
        User $user,
        string $rangeKey,
        CarbonImmutable $rangeStartsAt,
        CarbonImmutable $rangeEndsAt,
        string $spanPosition,
        bool $rangeIsAllDay,
    ): array {
        return [
            'id' => $id,
            'source' => $source,
            'block_type' => $blockType,
            'label' => $label,
            'color' => $color,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_all_day' => $isAllDay,
            'is_shared' => $isShared,
            'owner_id' => $user->id,
            'owner_name' => $user->name,
            'range_key' => $rangeKey,
            'range_starts_at' => $rangeStartsAt,
            'range_ends_at' => $rangeEndsAt,
            'span_position' => $spanPosition,
            'range_is_all_day' => $rangeIsAllDay,
            'time_label' => $this->formatRangeTimeLabel($rangeStartsAt, $rangeEndsAt, $rangeIsAllDay),
        ];
    }

    private function formatRangeTimeLabel(CarbonImmutable $startsAt, CarbonImmutable $endsAt, bool $isAllDay): string
    {
        if ($isAllDay) {
            if ($startsAt->isSameDay($endsAt)) {
                return 'All day';
            }

            return 'All day · '.$startsAt->format('M j').' – '.$endsAt->format('M j');
        }

        if ($startsAt->isSameDay($endsAt)) {
            return $startsAt->format('g:i A').' – '.$endsAt->format('g:i A');
        }

        return $startsAt->format('D g:i A').' → '.$endsAt->format('D g:i A');
    }

    private function timesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }
}
