@props([
    'panel',
])

@php
    $key = $panel['key'] ?? 'panel';
    $title = $panel['title'] ?? 'Activity';
    $summary = $panel['summary'] ?? null;
    $route = $panel['route'] ?? null;
    $routeLabel = $panel['route_label'] ?? 'View all';
    $items = $panel['items'] ?? [];
    $emptyMessage = $panel['empty_message'] ?? 'Nothing to show yet';
    $restricted = (bool) ($panel['restricted'] ?? false);
    $models = $panel['models'] ?? null;
    $unreadCount = (int) ($panel['unread_count'] ?? 0);

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
@endphp

<section class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">{{ $title }}</h2>
            @if ($summary)
                <p class="mt-1 text-xs font-medium text-slate-500">{{ $summary }}</p>
            @endif
        </div>

        @if ($key === 'notifications')
            <div class="flex flex-wrap items-center gap-2">
                @if ($unreadCount > 0)
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

                @if ($route)
                    <a
                        href="{{ route($route) }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gw-accent/50 bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
                    >
                        View All
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif
            </div>
        @elseif ($route && ! $restricted)
            <a
                href="{{ route($route) }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-gw-accent/50 bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-muted"
            >
                {{ $routeLabel }}
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                </svg>
            </a>
        @endif
    </div>

    @if ($key === 'notifications' && in_array(session('status'), ['notification-read', 'notifications-read'], true))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('status') === 'notifications-read' ? 'All notifications marked as read.' : 'Notification marked as read.' }}
        </div>
    @endif

    <div class="min-h-0 flex-1 space-y-3">
        @if ($restricted)
            <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                <p class="text-sm font-semibold text-gw-primary">{{ $title }} unavailable</p>
                <p class="mt-1 text-xs text-slate-500">{{ $emptyMessage }}</p>
            </div>
        @elseif ($key === 'notifications' && filled($models) && $models->isNotEmpty())
            @foreach ($models as $notification)
                @php
                    $category = data_get($notification->data, 'category', 'General');
                @endphp

                <x-notification-item
                    :notification="$notification"
                    :tone="$notificationTone($category)"
                    variant="dashboard"
                />
            @endforeach
        @elseif (count($items) > 0)
            <ul @class([
                'space-y-2',
                'max-h-80 overflow-y-auto pr-1' => $key === 'my_tasks',
            ])>
                @foreach ($items as $item)
                    <li @class([
                        'rounded-md border px-3 py-2.5 transition',
                        ($item['highlight'] ?? false)
                            ? 'border-gw-accent/40 bg-[#FFFDF5]'
                            : 'border-slate-100 bg-slate-50',
                    ])>
                        @if (filled($item['url'] ?? null))
                            <a href="{{ $item['url'] }}" class="group block">
                                @include('dashboard.partials.activity-item-content', ['item' => $item])
                            </a>
                        @else
                            @include('dashboard.partials.activity-item-content', ['item' => $item])
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                <p class="text-sm font-semibold text-gw-primary">{{ $emptyMessage }}</p>
            </div>
        @endif
    </div>
</section>
