<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

class CalendarEventsSectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    private const ITEM_LIMIT = 50;

    public function key(): string
    {
        return 'events';
    }

    public function label(): string
    {
        return 'Calendar Events';
    }

    public function icon(): string
    {
        return 'calendar';
    }

    public function sort(): int
    {
        return 40;
    }

    public function isEnabledFor(User $user): bool
    {
        return $user->can('view calendar') && Route::has('calendar.index');
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $events = CalendarEvent::query()
            ->with('type:id,name,slug')
            ->where('starts_at', '>=', now())
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($query) => $query->where('user_id', $user->id));
            })
            ->orderBy('starts_at')
            ->limit(self::ITEM_LIMIT)
            ->get();

        $items = $events
            ->map(function (CalendarEvent $event): array {
                $url = Route::has('calendar.events.show')
                    ? route('calendar.events.show', $event)
                    : (Route::has('calendar.index') ? route('calendar.index') : null);

                return [
                    'id' => 'event-'.$event->id,
                    'title' => $event->title,
                    'subtitle' => $event->is_all_day ? 'All day event' : ($event->type?->name ?? 'Calendar event'),
                    'meta' => $event->starts_at->format('M j · ').($event->is_all_day ? 'All day' : $event->starts_at->format('g:i A')),
                    'url' => $url,
                    'badge' => $event->starts_at->isToday() ? 'Today' : 'Upcoming',
                    'highlight' => $event->starts_at->isToday(),
                    'sort_at' => $event->starts_at->toIso8601String(),
                ];
            })
            ->all();

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No upcoming calendar events.',
            Route::has('calendar.index') ? ['label' => 'Open calendar', 'url' => route('calendar.index')] : null,
        );
    }
}
