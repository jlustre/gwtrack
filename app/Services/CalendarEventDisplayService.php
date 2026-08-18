<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalendarEventDisplayService
{
    /**
     * @param  Collection<int, CalendarEvent>  $events
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function segmentsByDate(Collection $events, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $byDate = [];

        foreach ($events as $event) {
            foreach ($this->segmentsForEvent($event, $rangeStart, $rangeEnd) as $dateKey => $segment) {
                $byDate[$dateKey] ??= collect();
                $byDate[$dateKey]->push($segment);
            }
        }

        foreach ($byDate as $dateKey => $segments) {
            $byDate[$dateKey] = $segments
                ->sortBy(fn (array $segment) => $segment['segment_starts_at'])
                ->values();
        }

        return $byDate;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function segmentsForEvent(CalendarEvent $event, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): array
    {
        $eventStartsAt = CarbonImmutable::parse($event->starts_at);
        $eventEndsAt = $event->ends_at ? CarbonImmutable::parse($event->ends_at) : $eventStartsAt;
        $startDate = $eventStartsAt->startOfDay();
        $endDate = $eventEndsAt->startOfDay();

        if ($startDate->gt($endDate)) {
            $endDate = $startDate;
        }

        $rangeStartsAt = $event->is_all_day
            ? $startDate->startOfDay()
            : $eventStartsAt;

        $rangeEndsAt = $event->is_all_day
            ? $endDate->endOfDay()
            : $eventEndsAt;

        $segments = [];
        $segmentStart = $startDate->greaterThan($rangeStart) ? $startDate : $rangeStart->startOfDay();
        $segmentEnd = $endDate->lessThan($rangeEnd) ? $endDate : $rangeEnd->startOfDay();

        if ($segmentStart->gt($segmentEnd)) {
            return [];
        }

        for ($day = $segmentStart; $day->lte($segmentEnd); $day = $day->addDay()) {
            $segment = $this->segmentForDay(
                $event,
                $day,
                $startDate,
                $endDate,
                $rangeStartsAt,
                $rangeEndsAt,
            );

            if ($segment !== null) {
                $segments[$day->toDateString()] = $segment;
            }
        }

        return $segments;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function segmentForDay(
        CalendarEvent $event,
        CarbonImmutable $day,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        CarbonImmutable $rangeStartsAt,
        CarbonImmutable $rangeEndsAt,
    ): ?array {
        $dayStart = $day->startOfDay();

        if ($event->is_all_day) {
            $spanPosition = match (true) {
                $startDate->equalTo($endDate) => 'single',
                $dayStart->equalTo($startDate) => 'start',
                $dayStart->equalTo($endDate) => 'end',
                default => 'middle',
            };

            return $this->buildSegmentPayload(
                $event,
                $day,
                $day->startOfDay(),
                $day->endOfDay(),
                $rangeStartsAt,
                $rangeEndsAt,
                $spanPosition,
                true,
            );
        }

        if ($startDate->equalTo($endDate)) {
            return $this->buildSegmentPayload(
                $event,
                $day,
                $event->starts_at ? CarbonImmutable::parse($event->starts_at) : $day->startOfDay(),
                $event->ends_at ? CarbonImmutable::parse($event->ends_at) : $day->endOfDay(),
                $rangeStartsAt,
                $rangeEndsAt,
                'single',
                false,
            );
        }

        if ($dayStart->equalTo($startDate)) {
            return $this->buildSegmentPayload(
                $event,
                $day,
                CarbonImmutable::parse($event->starts_at),
                $day->endOfDay(),
                $rangeStartsAt,
                $rangeEndsAt,
                'start',
                false,
            );
        }

        if ($dayStart->equalTo($endDate)) {
            return $this->buildSegmentPayload(
                $event,
                $day,
                $day->startOfDay(),
                CarbonImmutable::parse($event->ends_at),
                $rangeStartsAt,
                $rangeEndsAt,
                'end',
                false,
            );
        }

        return $this->buildSegmentPayload(
            $event,
            $day,
            $day->startOfDay(),
            $day->endOfDay(),
            $rangeStartsAt,
            $rangeEndsAt,
            'middle',
            false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSegmentPayload(
        CalendarEvent $event,
        CarbonImmutable $day,
        CarbonImmutable $segmentStartsAt,
        CarbonImmutable $segmentEndsAt,
        CarbonImmutable $rangeStartsAt,
        CarbonImmutable $rangeEndsAt,
        string $spanPosition,
        bool $rangeIsAllDay,
    ): array {
        return [
            'id' => 'event-'.$event->id.'-'.$day->toDateString(),
            'event' => $event,
            'span_position' => $spanPosition,
            'range_key' => 'event-'.$event->id,
            'range_starts_at' => $rangeStartsAt,
            'range_ends_at' => $rangeEndsAt,
            'segment_starts_at' => $segmentStartsAt,
            'segment_ends_at' => $segmentEndsAt,
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
}
