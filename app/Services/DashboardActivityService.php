<?php

namespace App\Services;

use App\Http\Controllers\TaskController;
use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Notifications\NotificationInboxService;
use App\Support\NotificationActionUrl;
use Illuminate\Support\Facades\Route;

class DashboardActivityService
{
    public function __construct(
        private readonly NotificationInboxService $notifications,
        private readonly TaskController $tasks,
        private readonly MessagingService $messaging,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function panelsFor(User $user): array
    {
        return [
            'my_tasks' => $this->myTasksPanel($user),
            'upcoming_meetings' => $this->upcomingMeetingsPanel($user),
            'calendar' => $this->calendarPanel($user),
            'notifications' => $this->notificationsPanel($user),
            'recent_messages' => $this->recentMessagesPanel($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationsPanel(User $user): array
    {
        $stats = $this->notifications->stats($user);
        $recent = $this->notifications->recent($user, 5);
        $items = $recent->map(function ($notification): array {
            return [
                'title' => data_get($notification->data, 'title')
                    ?? data_get($notification->data, 'subject')
                    ?? 'Portal notification',
                'subtitle' => str(data_get($notification->data, 'message')
                    ?? data_get($notification->data, 'body')
                    ?? '')->limit(120)->toString(),
                'meta' => $notification->created_at?->diffForHumans(),
                'url' => NotificationActionUrl::fromNotificationData($notification->data ?? []),
                'badge' => data_get($notification->data, 'category', 'General'),
                'highlight' => ! $notification->read(),
            ];
        })->all();

        return array_merge($this->panel(
            'notifications',
            'Notifications',
            ($stats['unread'] ?? 0) > 0
                ? $stats['unread'].' unread update'.($stats['unread'] === 1 ? '' : 's')
                : 'You are caught up on recent alerts',
            'notifications.index',
            $items,
            'No notifications yet',
            (int) ($stats['unread'] ?? 0),
        ), [
            'models' => $recent,
            'unread_count' => (int) ($stats['unread'] ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function myTasksPanel(User $user): array
    {
        $preview = $this->tasks->previewForDashboard($user, null);
        $count = (int) ($preview['count'] ?? 0);

        return $this->panel(
            'my_tasks',
            'My Tasks',
            $count.' incomplete task'.($count === 1 ? '' : 's').' assigned to you',
            'tasks.index',
            $preview['items'],
            'No open tasks — you are caught up',
            $count,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarPanel(User $user): array
    {
        if (! $user->can('view calendar')) {
            return $this->restrictedPanel('calendar', 'Calendar', 'Calendar access is not enabled for your account.');
        }

        $events = CalendarEvent::query()
            ->where('starts_at', '>=', now())
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($query) => $query->where('user_id', $user->id));
            })
            ->orderBy('starts_at')
            ->limit(5)
            ->get(['id', 'title', 'starts_at', 'is_all_day']);

        $items = $events->map(fn (CalendarEvent $event): array => [
            'title' => $event->title,
            'subtitle' => $event->is_all_day ? 'All day event' : null,
            'meta' => $event->starts_at->format('M j · ').($event->is_all_day ? 'All day' : $event->starts_at->format('g:i A')),
            'url' => route('calendar.events.show', $event),
        ])->all();

        return $this->panel(
            'calendar',
            'Calendar',
            count($items).' upcoming event'.(count($items) === 1 ? '' : 's'),
            'calendar.index',
            $items,
            'No upcoming calendar events',
            count($items),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function upcomingMeetingsPanel(User $user): array
    {
        if (! $user->can('view own bookings') && ! $user->can('view booking dashboard')) {
            return $this->restrictedPanel('upcoming_meetings', 'Upcoming Meetings', 'Booking access is not enabled for your account.');
        }

        $bookings = Booking::query()
            ->with(['eventType', 'cfm:id,name', 'trainee:id,name'])
            ->where(fn ($query) => $query->where('cfm_id', $user->id)->orWhere('trainee_id', $user->id))
            ->whereIn('status', ['confirmed', 'pending_approval'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $route = $user->can('view own bookings') ? 'bookings.my' : 'bookings.dashboard';

        $items = $bookings->map(function (Booking $booking) use ($user, $route): array {
            $counterpart = (int) $booking->cfm_id === (int) $user->id
                ? $booking->trainee?->name
                : $booking->cfm?->name;

            return [
                'title' => $booking->eventType?->name ?? 'Mentoring session',
                'subtitle' => filled($counterpart) ? 'With '.$counterpart : null,
                'meta' => $booking->starts_at->format('M j · g:i A').' · '.str_replace('_', ' ', $booking->status),
                'url' => Route::has($route) ? route($route) : null,
            ];
        })->all();

        return $this->panel(
            'upcoming_meetings',
            'Upcoming Meetings',
            count($items).' scheduled meeting'.(count($items) === 1 ? '' : 's'),
            Route::has($route) ? $route : null,
            $items,
            'No upcoming mentoring or booking meetings',
            count($items),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function recentMessagesPanel(User $user): array
    {
        if (! $user->can('view conversations')) {
            return $this->restrictedPanel('recent_messages', 'Recent Messages', 'Messaging is not enabled for your account.');
        }

        $stats = $this->messaging->dashboardStats($user);

        $items = $this->messaging->conversationSummaries($user)
            ->sortByDesc(fn (array $summary): int => (int) ($summary['unread'] ?? false))
            ->take(5)
            ->map(fn (array $summary): array => [
                'title' => $summary['name'] ?? 'Conversation',
                'subtitle' => $summary['last_message'] ?? null,
                'meta' => $summary['last_message_at'] ?? null,
                'url' => route('messages.index', ['conversation' => $summary['id'] ?? null]),
                'badge' => ($summary['unread'] ?? false) ? 'Unread' : null,
                'highlight' => (bool) ($summary['unread'] ?? false),
            ])
            ->values()
            ->all();

        return $this->panel(
            'recent_messages',
            'Recent Messages',
            ($stats['unread'] ?? 0).' unread · '.($stats['active'] ?? 0).' active conversation'.(($stats['active'] ?? 0) === 1 ? '' : 's'),
            'messages.index',
            $items,
            'No conversations yet',
            (int) ($stats['unread'] ?? 0),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function panel(
        string $key,
        string $title,
        ?string $summary,
        ?string $route,
        array $items,
        string $emptyMessage,
        int $count = 0,
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'summary' => $summary,
            'route' => $route,
            'route_label' => 'View all',
            'count' => $count,
            'items' => $items,
            'empty_message' => $emptyMessage,
            'restricted' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function restrictedPanel(string $key, string $title, string $message): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'summary' => $message,
            'route' => null,
            'route_label' => null,
            'count' => 0,
            'items' => [],
            'empty_message' => $message,
            'restricted' => true,
        ];
    }
}
