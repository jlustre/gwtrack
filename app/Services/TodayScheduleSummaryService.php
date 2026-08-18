<?php

namespace App\Services;

use App\Http\Controllers\TaskController;
use App\Models\CalendarEvent;
use App\Models\ProspectAppointment;
use App\Models\User;
use Illuminate\Support\Collection;

class TodayScheduleSummaryService
{
    public function __construct(
        private readonly TaskController $tasks,
    ) {}

    /**
     * @return array{
     *     date_label: string,
     *     total: int,
     *     buckets: list<array{key: string, label: string, count: int}>,
     *     tasks: array{label: string, count: int, url: string},
     *     calendar_url: string|null,
     *     appointments_url: string|null
     * }
     */
    public function forUser(User $user): array
    {
        $bucketDefinitions = config('today-schedule-summary.buckets', []);
        $counts = collect($bucketDefinitions)->mapWithKeys(fn (array $definition, string $key): array => [
            $key => 0,
        ])->all();

        $otherKey = 'other';
        $counts[$otherKey] = 0;

        foreach ($this->calendarEventsToday($user) as $event) {
            $bucket = $this->resolveBucket(
                (string) ($event->type?->slug ?? ''),
                null,
                $bucketDefinitions,
            );
            $counts[$bucket]++;
        }

        foreach ($this->standaloneAppointmentsToday($user) as $appointment) {
            $bucket = $this->resolveBucket(
                '',
                (string) ($appointment->type?->slug ?? ''),
                $bucketDefinitions,
            );
            $counts[$bucket]++;
        }

        $buckets = collect($bucketDefinitions)
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => (string) $definition['label'],
                'count' => (int) ($counts[$key] ?? 0),
            ])
            ->values()
            ->all();

        if (($counts[$otherKey] ?? 0) > 0) {
            $buckets[] = [
                'key' => $otherKey,
                'label' => (string) config('today-schedule-summary.other_bucket.label', 'Other Events'),
                'count' => (int) $counts[$otherKey],
            ];
        }

        $tasksDueToday = $this->tasks->previewDueTodayForDashboard($user);
        $scheduledTotal = collect($counts)->sum();

        return [
            'date_label' => now()->timezone($user->profile?->php_timezone ?? config('app.timezone'))->format('l, M j'),
            'total' => $scheduledTotal + (int) $tasksDueToday['count'],
            'buckets' => $buckets,
            'tasks' => [
                'label' => 'Tasks Due Today',
                'count' => (int) $tasksDueToday['count'],
                'url' => route('tasks.index'),
            ],
            'calendar_url' => route('calendar.index'),
            'appointments_url' => route('team.prospects.appointments'),
        ];
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    private function calendarEventsToday(User $user): Collection
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        return CalendarEvent::query()
            ->with('type:id,slug,name')
            ->whereIn('status', ['scheduled'])
            ->whereBetween('starts_at', [$start, $end])
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($query) => $query->where('user_id', $user->id));
            })
            ->get();
    }

    /**
     * Appointments not already represented by a synced calendar event.
     *
     * @return Collection<int, ProspectAppointment>
     */
    private function standaloneAppointmentsToday(User $user): Collection
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        return ProspectAppointment::query()
            ->with('type:id,slug,name')
            ->where('owner_id', $user->id)
            ->where('status', 'scheduled')
            ->whereNull('calendar_event_id')
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();
    }

    /**
     * @param  array<string, array<string, mixed>>  $bucketDefinitions
     */
    private function resolveBucket(string $calendarTypeSlug, ?string $appointmentTypeSlug, array $bucketDefinitions): string
    {
        foreach ($bucketDefinitions as $key => $definition) {
            $calendarSlugs = $definition['calendar_type_slugs'] ?? [];
            $appointmentSlugs = $definition['appointment_type_slugs'] ?? [];

            if ($calendarTypeSlug !== '' && in_array($calendarTypeSlug, $calendarSlugs, true)) {
                return (string) $key;
            }

            if (filled($appointmentTypeSlug) && in_array($appointmentTypeSlug, $appointmentSlugs, true)) {
                return (string) $key;
            }
        }

        if ($calendarTypeSlug === '' && ! filled($appointmentTypeSlug)) {
            return 'other';
        }

        return (string) config('today-schedule-summary.fallback_bucket', 'meetings');
    }
}
