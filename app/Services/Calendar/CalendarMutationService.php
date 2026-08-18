<?php

namespace App\Services\Calendar;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventActivityLog;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventNote;
use App\Models\CalendarEventRecurrence;
use App\Models\CalendarEventReminder;
use App\Models\CalendarEventType;
use App\Models\User;
use App\Models\UserCalendarPreference;
use App\Services\Notifications\NotificationOrchestrator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CalendarMutationService
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
        private readonly CalendarWorkspaceService $workspace,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createEvent(User $user, array $validated): CalendarEvent
    {
        $type = filled($validated['calendar_event_type_id'] ?? null)
            ? CalendarEventType::query()->find($validated['calendar_event_type_id'])
            : null;
        $category = filled($validated['calendar_category_id'] ?? null)
            ? CalendarCategory::query()->find($validated['calendar_category_id'])
            : null;

        if ($category && ! $this->workspace->assignableCategories($user)->contains('id', $category->id)) {
            throw ValidationException::withMessages([
                'calendar_category_id' => 'Select a calendar category you are allowed to use.',
            ]);
        }

        $resolvedCategory = $category ?? $type?->category;
        $categoryId = $resolvedCategory?->id;
        $color = $resolvedCategory?->color ?? $type?->color ?? '#84BB41';
        $isRecurring = (bool) ($validated['is_recurring'] ?? false);
        $recurrence = $isRecurring ? $this->recurrenceData($validated) : null;

        $event = CalendarEvent::create([
            'calendar_event_type_id' => $type?->id,
            'calendar_category_id' => $categoryId,
            'organizer_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'timezone' => $validated['timezone'],
            'is_all_day' => (bool) ($validated['is_all_day'] ?? false),
            'is_recurring' => $isRecurring,
            'recurrence_rule' => $recurrence['rule'] ?? null,
            'location' => $validated['location'] ?? null,
            'meeting_link' => $validated['meeting_link'] ?? null,
            'visibility' => $validated['visibility'],
            'status' => $validated['status'],
            'color' => $color,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($recurrence) {
            CalendarEventRecurrence::create([
                'calendar_event_id' => $event->id,
                'frequency' => $recurrence['frequency'],
                'interval' => $recurrence['interval'],
                'weekdays' => $recurrence['weekdays'],
                'ends_after_occurrences' => $recurrence['ends_after_occurrences'],
                'ends_on' => $recurrence['ends_on'],
            ]);
        }

        collect($validated['attendee_user_ids'] ?? [])
            ->unique()
            ->reject(fn (int|string $userId): bool => (int) $userId === $user->id)
            ->each(fn (int|string $userId) => CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'user_id' => (int) $userId,
                'attendee_type' => 'user',
                'rsvp_status' => 'pending',
            ]));

        $attendeeIds = collect($validated['attendee_user_ids'] ?? [])
            ->unique()
            ->reject(fn (int|string $userId): bool => (int) $userId === $user->id)
            ->map(fn (int|string $userId) => (int) $userId)
            ->values()
            ->all();

        if ($validated['status'] === 'scheduled' && $attendeeIds !== []) {
            $this->notifications->dispatch('calendar_event_invited', [
                'queue' => true,
                'sender' => $user,
                'recipients' => ['user_ids' => $attendeeIds],
                'module' => 'calendar',
                'priority' => 'medium',
                'related' => ['type' => CalendarEvent::class, 'id' => $event->id],
                'template_data' => [
                    'event_title' => $event->title,
                    'organizer_name' => $user->name,
                    'session_time' => $event->starts_at?->format('M j, Y g:i A') ?? '',
                ],
                'action_link' => [
                    'route' => 'calendar.index',
                    'label' => 'View calendar',
                ],
            ]);
        }

        if (filled($validated['external_attendee_email'] ?? null) || filled($validated['external_attendee_name'] ?? null)) {
            CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'name' => $validated['external_attendee_name'] ?? null,
                'email' => $validated['external_attendee_email'] ?? null,
                'attendee_type' => 'external',
                'rsvp_status' => 'pending',
            ]);
        }

        foreach (array_unique(Arr::wrap($validated['reminder_minutes'] ?? [15])) as $minutesBefore) {
            CalendarEventReminder::create([
                'calendar_event_id' => $event->id,
                'user_id' => $user->id,
                'minutes_before' => (int) $minutesBefore,
                'channel' => $validated['reminder_channel'],
            ]);
        }

        if (filled($validated['notes'] ?? null)) {
            CalendarEventNote::create([
                'calendar_event_id' => $event->id,
                'created_by' => $user->id,
                'note' => $validated['notes'],
                'is_private' => false,
            ]);
        }

        CalendarEventActivityLog::create([
            'calendar_event_id' => $event->id,
            'user_id' => $user->id,
            'action' => 'created',
            'payload' => [
                'visibility' => $event->visibility,
                'status' => $event->status,
                'attendee_count' => $event->attendees()->count(),
            ],
        ]);

        return $event;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function storeCategory(User $user, array $validated): CalendarCategory
    {
        $slug = $this->uniqueCategorySlug($user->id, $validated['name']);

        $category = CalendarCategory::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => strtoupper($validated['color']),
            'icon' => 'calendar',
            'sort_order' => (int) (CalendarCategory::query()->where('user_id', $user->id)->max('sort_order') ?? 900) + 10,
            'is_active' => true,
            'is_public' => (bool) ($validated['is_public'] ?? false),
        ]);

        // Ensure preference exists, then include the new calendar in the sidebar selection.
        $this->workspace->selectedCalendarIds($user);
        $preference = UserCalendarPreference::query()->where('user_id', $user->id)->firstOrFail();
        $preference->update([
            'visible_calendar_categories' => collect($preference->visible_calendar_categories ?? [])
                ->push($category->id)
                ->unique()
                ->values()
                ->all(),
        ]);

        return $category;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateCategory(User $user, CalendarCategory $category, array $validated): void
    {
        abort_unless($this->canManageCategory($user, $category), 403);

        $category->update([
            'name' => $validated['name'],
            'color' => strtoupper($validated['color']),
            'is_public' => $category->isSystem() ? false : (bool) ($validated['is_public'] ?? false),
        ]);
    }

    public function destroyCategory(User $user, CalendarCategory $category): void
    {
        abort_unless($this->canManageCategory($user, $category), 403);

        $categoryId = (int) $category->id;
        $category->delete();

        $preference = UserCalendarPreference::query()->where('user_id', $user->id)->first();
        if ($preference) {
            $preference->update([
                'visible_calendar_categories' => collect($preference->visible_calendar_categories ?? [])
                    ->reject(fn ($id): bool => (int) $id === $categoryId)
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function canManageCategory(User $user, CalendarCategory $category): bool
    {
        if ($category->isOwnedBy($user)) {
            return true;
        }

        return $category->isSystem() && $user->hasPermissionTo('manage organization calendar');
    }

    /**
     * @return Collection<int, CalendarCategory>
     */
    public function assignableCategories(User $user): Collection
    {
        return $this->workspace->assignableCategories($user);
    }

    private function uniqueCategorySlug(int $userId, string $name): string
    {
        $base = 'u'.$userId.'-'.Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (CalendarCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function recurrenceData(array $validated): array
    {
        $frequency = $validated['recurrence_frequency'] ?? 'weekly';
        $interval = (int) ($validated['recurrence_interval'] ?? 1);
        $start = CarbonImmutable::parse($validated['starts_at']);
        $weekdays = $frequency === 'weekly'
            ? array_values(array_unique($validated['recurrence_weekdays'] ?? [$this->weekdayCode($start)]))
            : null;
        $endType = $validated['recurrence_end_type'] ?? 'never';
        $endsAfter = $endType === 'after' ? (int) ($validated['recurrence_ends_after_occurrences'] ?? 12) : null;
        $endsOn = $endType === 'on' ? $validated['recurrence_ends_on'] ?? null : null;

        $ruleParts = [
            'FREQ='.strtoupper($frequency),
            'INTERVAL='.$interval,
        ];

        if ($weekdays) {
            $ruleParts[] = 'BYDAY='.implode(',', $weekdays);
        }

        if ($endsAfter) {
            $ruleParts[] = 'COUNT='.$endsAfter;
        }

        if ($endsOn) {
            $ruleParts[] = 'UNTIL='.CarbonImmutable::parse($endsOn)->format('Ymd');
        }

        return [
            'frequency' => $frequency,
            'interval' => $interval,
            'weekdays' => $weekdays,
            'ends_after_occurrences' => $endsAfter,
            'ends_on' => $endsOn,
            'rule' => implode(';', $ruleParts),
        ];
    }

    private function weekdayCode(CarbonImmutable $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA',
            default => 'SU',
        };
    }
}
