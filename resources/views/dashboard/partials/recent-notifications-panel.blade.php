@php
    $user = auth()->user();
    $notificationLimit = (int) ($limit ?? 5);
    $dashboardNotifications = $user?->notifications()->latest()->limit($notificationLimit)->get() ?? collect();
    $dashboardUnreadNotificationCount = $user?->unreadNotifications()->count() ?? 0;

    $notificationTone = function (?string $category): string {
        return match (strtolower((string) $category)) {
            'mentorship', 'mentor assignment' => 'bg-gw-accent',
            'training' => 'bg-emerald-500',
            'licensing' => 'bg-red-500',
            'event', 'events' => 'bg-sky-500',
            'rank advancement' => 'bg-purple-500',
            'announcement', 'announcements' => 'bg-orange-500',
            default => 'bg-gw-primary',
        };
    };

    $notificationActionUrl = function ($notification): ?string {
        return data_get($notification->data, 'action_url')
            ?? data_get($notification->data, 'action_link.url')
            ?? data_get($notification->data, 'action_link');
    };
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Notifications</h2>
            @if ($dashboardUnreadNotificationCount > 0)
                <p class="mt-1 text-xs font-medium text-slate-500">{{ $dashboardUnreadNotificationCount }} unread update{{ $dashboardUnreadNotificationCount === 1 ? '' : 's' }}</p>
            @else
                <p class="mt-1 text-xs font-medium text-slate-500">You're caught up on recent alerts.</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($dashboardUnreadNotificationCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-gw-accent hover:bg-gw-accent-soft hover:text-gw-primary"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.32a1 1 0 0 1-1.42.002L3.29 9.776a1 1 0 1 1 1.334-1.49l4.04 3.617 6.62-6.688a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                        Mark All Read
                    </button>
                </form>
            @endif

            <a
                href="{{ route('notifications.index') }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-gw-accent/50 bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
            >
                View All
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>

    @if (session('status') === 'notification-read')
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            Notification marked as read.
        </div>
    @elseif (session('status') === 'notifications-read')
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            All notifications marked as read.
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($dashboardNotifications as $notification)
            @php
                $category = data_get($notification->data, 'category', 'General');
                $actionUrl = $notificationActionUrl($notification);
            @endphp

            <x-notification-item
                :notification="$notification"
                :tone="$notificationTone($category)"
                :action-url="$actionUrl"
                variant="dashboard"
            />
        @empty
            <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gw-primary/5 text-gw-primary">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-gw-primary">No notifications yet</p>
                <p class="mt-1 text-xs text-slate-500">Mentorship, training, licensing, and team alerts will appear here.</p>
            </div>
        @endforelse
    </div>
</section>
