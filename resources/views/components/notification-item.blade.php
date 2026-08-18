@props([
    'notification',
    'tone' => 'bg-gw-primary',
    'actionUrl' => null,
    'variant' => 'dashboard',
])

@php
    use App\Support\NotificationActionUrl;

    $title = data_get($notification->data, 'title')
        ?? data_get($notification->data, 'subject')
        ?? 'Portal notification';

    $message = data_get($notification->data, 'message')
        ?? data_get($notification->data, 'body')
        ?? 'Open notifications to review the latest portal activity.';

    $category = data_get($notification->data, 'category', 'General');
    $isRead = $notification->read();
    $isDashboard = $variant === 'dashboard';
    $resolvedActionUrl = filled($actionUrl)
        ? NotificationActionUrl::normalize($actionUrl)
        : NotificationActionUrl::fromNotificationData($notification->data ?? []);
@endphp

<article
    x-data="{ expanded: false }"
    {{ $attributes->class([
        'transition',
        $isDashboard
            ? ($isRead ? 'rounded-md border border-slate-100 bg-slate-50 px-4 py-3' : 'rounded-md border border-gw-accent/30 bg-[#FFFDF5] px-4 py-3 shadow-sm')
            : 'hover:bg-slate-50',
    ]) }}
>
    <div @class(['flex', $isDashboard ? 'gap-3' : 'gap-4'])>
        <div @class([
            'shrink-0 rounded-full',
            $tone,
            $isDashboard ? 'mt-1.5 h-2.5 w-2.5' : 'mt-1 h-3 w-3',
        ])></div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between sm:gap-3">
                <div class="flex min-w-0 flex-1 items-start gap-2">
                    <h3 class="min-w-0 flex-1 text-sm font-semibold text-gw-primary">
                        {{ $title }}
                    </h3>

                    <button
                        type="button"
                        x-on:click="expanded = ! expanded"
                        :aria-expanded="expanded"
                        :title="expanded ? 'Collapse notification' : 'Expand notification'"
                        class="gw-icon-btn mt-0.5"
                    >
                        <span class="sr-only" x-text="expanded ? 'Collapse' : 'Expand'"></span>
                        <svg
                            class="h-3.5 w-3.5 transition-transform duration-200"
                            :class="expanded ? 'rotate-180' : ''"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <span @class([
                    'w-fit shrink-0 rounded-full font-semibold',
                    $isDashboard ? 'px-2 py-1 text-[0.68rem]' : 'px-2 py-1 text-xs',
                    $isRead ? 'bg-slate-100 text-slate-500' : 'bg-gw-accent/20 text-gw-primary',
                ])>
                    @if ($isDashboard)
                        {{ $isRead ? 'Read' : 'New' }}
                    @else
                        {{ $isRead ? 'Read' : 'Unread' }}
                    @endif
                </span>
            </div>

            <p
                @class([
                    'text-slate-600',
                    $isDashboard ? 'mt-1 text-xs leading-5' : 'mt-2 text-sm leading-6',
                    'line-clamp-2',
                ])
                :class="{ 'line-clamp-none': expanded }"
            >
                {{ $message }}
            </p>

            <div
                x-show="expanded"
                x-transition.opacity.duration.150ms
                class="mt-3 flex flex-wrap items-center {{ $isDashboard ? 'gap-2' : 'gap-3' }}"
            >
                <p @class([
                    'font-medium uppercase tracking-wide text-slate-400',
                    $isDashboard ? 'text-[0.68rem]' : 'text-xs',
                ])>
                    {{ $category }} &middot; {{ $notification->created_at->diffForHumans() }}
                </p>

                @unless ($isRead)
                    <form method="POST" action="{{ route('notifications.mark-read', $notification->id) }}">
                        @csrf
                        <button
                            type="submit"
                            @class([
                                'inline-flex items-center font-semibold text-slate-700 transition hover:border-gw-accent hover:bg-gw-accent-soft hover:text-gw-primary',
                                $isDashboard
                                    ? 'gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[0.68rem]'
                                    : 'rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs',
                            ])
                        >
                            @if ($isDashboard)
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.32a1 1 0 0 1-1.42.002L3.29 9.776a1 1 0 1 1 1.334-1.49l4.04 3.617 6.62-6.688a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                            Mark Read
                        </button>
                    </form>
                @endunless

                @if (filled($resolvedActionUrl))
                    <a
                        href="{{ $resolvedActionUrl }}"
                        @class([
                            'inline-flex items-center font-semibold text-gw-primary transition hover:bg-gw-accent-muted',
                            $isDashboard
                                ? 'gap-1 rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[0.68rem]'
                                : 'gap-1 rounded-md border border-gw-accent/50 bg-gw-accent-soft px-3 py-1.5 text-xs',
                        ])
                    >
                        Open
                        @if ($isDashboard)
                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 .75.75v8.5a.75.75 0 0 1-1.5 0V7.06l-6.22 6.22a.75.75 0 0 1-1.06-1.06L11.94 6H5.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M13.25 3.25a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V5.56l-6.22 6.22a.75.75 0 1 1-1.06-1.06l6.22-6.22h-1.69a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>
</article>
