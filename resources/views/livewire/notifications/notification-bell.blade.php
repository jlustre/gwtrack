<div
    class="relative"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    wire:poll.{{ config('notifications.bell_poll_seconds', 60) }}s="refreshFeed"
>
    {{-- Toast stack --}}
    <div class="pointer-events-none fixed bottom-4 right-4 z-[70] flex w-full max-w-sm flex-col gap-2">
        @foreach ($toasts as $index => $toast)
            <div
                wire:key="toast-{{ $index }}"
                class="pointer-events-auto rounded-xl border border-gw-accent/40 bg-gw-primary p-4 text-white shadow-xl"
                x-data="{ show: true }"
                x-show="show"
                x-transition
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">{{ \App\Support\NotificationPresentation::priorityLabel($toast['priority']) }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ $toast['title'] }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-300">{{ $toast['message'] }}</p>
                    </div>
                    <button type="button" wire:click="dismissToast({{ $index }})" class="text-slate-400 hover:text-white">&times;</button>
                </div>
            </div>
        @endforeach
    </div>

    <button
        type="button"
        title="{{ $unreadCount > 0 ? $unreadCount.' unread notifications' : 'Notifications' }}"
        class="gw-icon-btn-lg relative"
        @click="open = !open"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <span class="sr-only">Open notifications</span>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gw-accent px-1 text-[0.65rem] font-bold text-gw-primary">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 px-4 py-3">
            <div class="text-sm font-semibold text-gw-primary">Notifications</div>
            <div class="text-xs text-slate-500">{{ $unreadCount }} unread update{{ $unreadCount === 1 ? '' : 's' }}</div>
        </div>

        <div class="max-h-80 overflow-y-auto py-1">
            @forelse ($items as $item)
                <div wire:key="bell-item-{{ $item['id'] }}" class="flex gap-2 px-4 py-3 transition hover:bg-slate-50">
                    <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', $item['tone']])></span>
                    <div class="min-w-0 flex-1">
                        <button
                            type="button"
                            wire:click="openRelated(@js($item['id']))"
                            class="w-full text-left"
                        >
                            <div class="text-sm font-medium text-gw-primary">{{ $item['title'] }}</div>
                            <div class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $item['message'] }}</div>
                            <div class="mt-1 text-[0.65rem] text-slate-400">{{ $item['created_human'] }}</div>
                        </button>
                    </div>
                    @unless ($item['is_read'])
                        <button
                            type="button"
                            wire:click="markRead(@js($item['id']))"
                            title="Mark as read"
                            class="gw-icon-btn mt-0.5 shrink-0"
                        >
                            <span class="sr-only">Mark as read</span>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.32a1 1 0 0 1-1.42.002L3.29 9.776a1 1 0 1 1 1.334-1.49l4.04 3.617 6.62-6.688a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endunless
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-slate-500">No notifications yet.</div>
            @endforelse
        </div>

        <div class="grid grid-cols-2 border-t border-slate-100">
            <a href="{{ route('notifications.index') }}" class="px-4 py-3 text-center text-sm font-semibold text-gw-primary hover:bg-slate-50">View All</a>
            <button
                type="button"
                wire:click="markAllRead"
                @disabled($unreadCount === 0)
                class="border-l border-slate-100 px-4 py-3 text-center text-sm font-semibold text-gw-primary hover:bg-slate-50 disabled:opacity-50"
            >
                Mark All Read
            </button>
        </div>
    </div>
</div>
