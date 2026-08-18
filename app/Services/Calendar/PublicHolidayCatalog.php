<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PublicHolidayCatalog
{
    public const US_CATEGORY_SLUG = 'us-public-holidays';

    public const CANADA_CATEGORY_SLUG = 'canada-public-holidays';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function calendarDefinitions(): array
    {
        return config('public-holidays.calendars', []);
    }

    /**
     * @return list<int>
     */
    public function yearsForRollingWindow(?int $anchorYear = null): array
    {
        $current = $anchorYear ?? (int) now()->year;
        $yearsBack = max(0, (int) config('public-holidays.window.years_back', 1));
        $yearsAhead = max(0, (int) config('public-holidays.window.years_ahead', 2));

        return range($current - $yearsBack, $current + $yearsAhead);
    }

    /**
     * @return Collection<int, array{name: string, date: CarbonImmutable, calendar_slug: string}>
     */
    public function holidaysForCalendar(string $calendarSlug, int $year): Collection
    {
        $definition = $this->calendarDefinitions()[$calendarSlug] ?? null;

        if ($definition === null) {
            return collect();
        }

        return collect($definition['holidays'] ?? [])
            ->map(function (array $holiday) use ($calendarSlug, $year): array {
                return [
                    'name' => (string) $holiday['name'],
                    'date' => $this->resolveRuleDate($year, (array) ($holiday['rule'] ?? [])),
                    'calendar_slug' => $calendarSlug,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, date: CarbonImmutable, calendar_slug: string}>
     */
    public function holidaysForYear(int $year): Collection
    {
        return collect($this->calendarDefinitions())
            ->keys()
            ->flatMap(fn (string $slug): Collection => $this->holidaysForCalendar($slug, $year));
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    public function resolveRuleDate(int $year, array $rule): CarbonImmutable
    {
        return match ($rule['type'] ?? null) {
            'fixed' => $this->resolveFixedDate(
                $year,
                (int) $rule['month'],
                (int) $rule['day'],
                (bool) ($rule['observe'] ?? false),
            ),
            'nth_weekday' => $this->nthWeekdayOfMonth(
                $year,
                (int) $rule['month'],
                (int) $rule['weekday'],
                (int) $rule['occurrence'],
            ),
            'last_weekday' => $this->lastWeekdayOfMonth(
                $year,
                (int) $rule['month'],
                (int) $rule['weekday'],
            ),
            'weekday_on_or_before' => $this->weekdayOnOrBefore(
                $year,
                (int) $rule['month'],
                (int) $rule['day'],
                (int) ($rule['weekday'] ?? 1),
            ),
            'easter_offset' => $this->easterSunday($year)->addDays((int) ($rule['days'] ?? 0)),
            default => throw new InvalidArgumentException('Unsupported public holiday rule type.'),
        };
    }

    private function resolveFixedDate(int $year, int $month, int $day, bool $observe): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, $day)->startOfDay();

        return $observe ? $this->observedDate($date) : $date;
    }

    private function observedDate(CarbonImmutable $date): CarbonImmutable
    {
        return match ($date->dayOfWeekIso) {
            6 => $date->subDay(),
            7 => $date->addDay(),
            default => $date,
        };
    }

    private function nthWeekdayOfMonth(int $year, int $month, int $weekdayIso, int $occurrence): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, 1)->startOfDay();

        while ($date->dayOfWeekIso !== $weekdayIso) {
            $date = $date->addDay();
        }

        return $date->addWeeks($occurrence - 1);
    }

    private function lastWeekdayOfMonth(int $year, int $month, int $weekdayIso): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, 1)->endOfMonth()->startOfDay();

        while ($date->dayOfWeekIso !== $weekdayIso) {
            $date = $date->subDay();
        }

        return $date;
    }

    private function weekdayOnOrBefore(int $year, int $month, int $day, int $weekdayIso): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, $day)->startOfDay();

        while ($date->dayOfWeekIso !== $weekdayIso) {
            $date = $date->subDay();
        }

        return $date;
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }
}
