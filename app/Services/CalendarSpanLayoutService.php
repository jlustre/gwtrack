<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalendarSpanLayoutService
{
    /**
     * @param  Collection<int, CarbonImmutable>  $days
     * @param  array<string, Collection<int, array<string, mixed>>>  $ownBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $sharedBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $eventSegmentsByDate
     * @return array<int, array<string, array{type: string, payload: array<string, mixed>}|null>>
     */
    public function spanningRows(
        Collection $days,
        array $ownBlocksByDate,
        array $sharedBlocksByDate,
        array $eventSegmentsByDate,
    ): array {
        $ranges = [];

        foreach ($days as $day) {
            foreach ($this->itemsForDate($ownBlocksByDate, $sharedBlocksByDate, $eventSegmentsByDate, $day->toDateString()) as $item) {
                if (! $this->spansMultipleDays($item['payload'])) {
                    continue;
                }

                $key = (string) ($item['payload']['range_key'] ?? $item['payload']['id']);
                $ranges[$key] ??= [
                    'range_starts_at' => $item['payload']['range_starts_at'] ?? $item['payload']['starts_at'],
                    'items' => [],
                ];
                $ranges[$key]['items'][$day->toDateString()] = $item;
            }
        }

        uasort(
            $ranges,
            fn (array $left, array $right): int => ($left['range_starts_at'] ?? now()) <=> ($right['range_starts_at'] ?? now()),
        );

        $rows = [];

        foreach ($ranges as $range) {
            $row = [];

            foreach ($days as $day) {
                $row[$day->toDateString()] = $range['items'][$day->toDateString()] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $days
     * @param  array<string, Collection<int, array<string, mixed>>>  $ownBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $sharedBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $eventSegmentsByDate
     * @return array<string, Collection<int, array{type: string, payload: array<string, mixed>}>>
     */
    public function singlesByDate(
        Collection $days,
        array $ownBlocksByDate,
        array $sharedBlocksByDate,
        array $eventSegmentsByDate,
    ): array {
        $singles = [];

        foreach ($days as $day) {
            $dateKey = $day->toDateString();

            $singles[$dateKey] = $this->itemsForDate($ownBlocksByDate, $sharedBlocksByDate, $eventSegmentsByDate, $dateKey)
                ->filter(fn (array $item): bool => ! $this->spansMultipleDays($item['payload']))
                ->values();
        }

        return $singles;
    }

    /**
     * @param  array<string, Collection<int, array<string, mixed>>>  $ownBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $sharedBlocksByDate
     * @param  array<string, Collection<int, array<string, mixed>>>  $eventSegmentsByDate
     * @return Collection<int, array{type: string, payload: array<string, mixed>}>
     */
    private function itemsForDate(
        array $ownBlocksByDate,
        array $sharedBlocksByDate,
        array $eventSegmentsByDate,
        string $dateKey,
    ): Collection {
        $blocks = collect($ownBlocksByDate[$dateKey] ?? [])
            ->merge(collect($sharedBlocksByDate[$dateKey] ?? []))
            ->map(fn (array $block): array => ['type' => 'block', 'payload' => $block]);

        $events = collect($eventSegmentsByDate[$dateKey] ?? [])
            ->map(fn (array $segment): array => ['type' => 'event', 'payload' => $segment]);

        return $blocks
            ->merge($events)
            ->sortBy(function (array $item): string {
                $payload = $item['payload'];

                return ($payload['range_starts_at'] ?? $payload['starts_at'] ?? now())->toDateTimeString()
                    .'-'.($payload['range_key'] ?? $payload['id'] ?? '');
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function spansMultipleDays(array $item): bool
    {
        $rangeStart = $item['range_starts_at'] ?? $item['starts_at'] ?? null;
        $rangeEnd = $item['range_ends_at'] ?? $item['ends_at'] ?? null;

        if ($rangeStart === null || $rangeEnd === null) {
            return false;
        }

        return ! CarbonImmutable::parse($rangeStart)->startOfDay()->equalTo(
            CarbonImmutable::parse($rangeEnd)->startOfDay()
        );
    }
}
