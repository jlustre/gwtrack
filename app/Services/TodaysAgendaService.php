<?php

namespace App\Services;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\User;
use Illuminate\Support\Carbon;

class TodaysAgendaService
{
    /**
     * @return array{
     *     date_label: string,
     *     total: int,
     *     has_items: bool,
     *     sections: list<array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $user->loadMissing('profile.timezoneRecord');

        [$dayStart, $dayEnd, $dateLabel] = $this->dayBounds($user);

        $sections = collect($this->providers())
            ->filter(fn (TodaysAgendaSectionProvider $provider): bool => $provider->isEnabledFor($user))
            ->sortBy(fn (TodaysAgendaSectionProvider $provider): int => $provider->sort())
            ->map(fn (TodaysAgendaSectionProvider $provider): array => $provider->sectionFor($user, $dayStart, $dayEnd))
            ->when(
                ! config('todays-agenda.show_empty_sections_in_modal', true),
                fn ($collection) => $collection->filter(fn (array $section): bool => ($section['count'] ?? 0) > 0),
            )
            ->values()
            ->all();

        $total = (int) collect($sections)->sum('count');

        return [
            'date_label' => $dateLabel,
            'total' => $total,
            'has_items' => $total > 0,
            'sections' => $sections,
        ];
    }

    /**
     * @return array{date_label: string, total: int, has_items: bool}
     */
    public function summaryFor(User $user): array
    {
        $agenda = $this->forUser($user);

        return [
            'date_label' => $agenda['date_label'],
            'total' => $agenda['total'],
            'has_items' => $agenda['has_items'],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function dayBounds(User $user): array
    {
        $timezone = $user->profile?->php_timezone ?? config('app.timezone');
        $now = now()->timezone($timezone);

        return [
            $now->copy()->startOfDay(),
            $now->copy()->endOfDay(),
            $now->format('l, M j'),
        ];
    }

    /**
     * @return list<TodaysAgendaSectionProvider>
     */
    private function providers(): array
    {
        return collect(config('todays-agenda.providers', []))
            ->map(fn (string $providerClass): TodaysAgendaSectionProvider => app($providerClass))
            ->filter(fn (mixed $provider): bool => $provider instanceof TodaysAgendaSectionProvider)
            ->values()
            ->all();
    }
}
