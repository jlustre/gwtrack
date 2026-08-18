<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Notification Center</p>
            <h1 class="text-2xl font-semibold text-gw-primary">All notifications</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Mentorship, training, licensing, tasks, prospects, goals, messages, and system alerts — all in one place.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('notifications.preferences') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-gw-accent hover:bg-gw-accent-soft"
            >
                Preferences
            </a>
            <button
                type="button"
                wire:click="markAllRead"
                @disabled($stats['unread'] === 0)
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-gw-accent hover:bg-gw-accent-soft disabled:opacity-50"
            >
                Mark All Read
            </button>
            <button
                type="button"
                wire:click="toggleArchived"
                @class([
                    'rounded-lg px-4 py-2 text-sm font-semibold transition',
                    $showArchived ? 'bg-gw-primary text-gw-accent' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
                ])
            >
                {{ $showArchived ? 'Back to Inbox' : 'Archived' }}
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Unread', 'value' => $stats['unread'], 'theme' => 'gold', 'subtitle' => 'Needs your attention'],
                ['label' => 'Read', 'value' => $stats['read'], 'theme' => 'navy', 'subtitle' => 'Already reviewed'],
                ['label' => 'Inbox', 'value' => $stats['total'], 'theme' => 'emerald', 'subtitle' => 'Active notifications'],
                ['label' => 'Archived', 'value' => $stats['archived'], 'theme' => 'cyan', 'subtitle' => 'Stored for reference'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    @unless ($showArchived)
        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-1">
            @foreach ($tabs as $key => $tab)
                <button
                    type="button"
                    wire:click="setTab(@js($key))"
                    @class([
                        'rounded-t-lg px-3 py-2 text-sm font-semibold transition',
                        $activeTab === $key
                            ? 'border border-b-white border-slate-200 bg-white text-gw-primary'
                            : 'text-slate-500 hover:text-gw-primary',
                    ])
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    @endunless

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search notifications…"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent sm:max-w-md"
        />
        <select wire:model.live="priorityFilter" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            <option value="">All priorities</option>
            @foreach ($priorities as $code => $meta)
                <option value="{{ $code }}">{{ $meta['label'] }}</option>
            @endforeach
        </select>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                @php($item = $summarize($notification))
                <article
                    wire:key="center-item-{{ $notification->id }}"
                    @class([
                        'px-5 py-4 transition',
                        $item['is_read'] ? 'bg-white' : 'border-l-4 border-l-gw-accent bg-gw-accent-soft/40',
                    ])
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 flex-1 gap-3">
                            <span @class(['mt-2 h-3 w-3 shrink-0 rounded-full', $item['tone']])></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-gw-primary">{{ $item['title'] }}</h3>
                                    <span class="rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold {{ \App\Support\NotificationPresentation::priorityBadgeClasses($item['priority']) }}">
                                        {{ \App\Support\NotificationPresentation::priorityLabel($item['priority']) }}
                                    </span>
                                    @unless ($item['is_read'])
                                        <span class="rounded-full bg-gw-accent/20 px-2 py-0.5 text-[0.65rem] font-bold text-gw-primary">New</span>
                                    @endunless
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['message'] }}</p>
                                <p class="mt-2 text-xs text-slate-400">{{ $item['category'] }} · {{ $item['created_human'] }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            @if ($item['action_url'])
                                <button
                                    type="button"
                                    wire:click="openNotification(@js($notification->id))"
                                    class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-muted"
                                >
                                    Open
                                </button>
                            @endif

                            @if ($item['is_read'])
                                <button type="button" wire:click="markUnread(@js($notification->id))" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Mark Unread</button>
                            @else
                                <button type="button" wire:click="markRead(@js($notification->id))" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Mark Read</button>
                            @endif

                            @if ($showArchived)
                                <button type="button" wire:click="unarchive(@js($notification->id))" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Restore</button>
                            @else
                                <button type="button" wire:click="archive(@js($notification->id))" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Archive</button>

                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Snooze</button>
                                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-40 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                        @foreach ($snoozeOptions as $code => $option)
                                            <button
                                                type="button"
                                                wire:click="snooze(@js($notification->id), @js($code))"
                                                @click="open = false"
                                                class="block w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                            >
                                                {{ $option['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <button
                                type="button"
                                wire:click="delete(@js($notification->id))"
                                wire:confirm="Delete this notification?"
                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ $showArchived ? 'No archived notifications.' : 'No notifications match this filter.' }}
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
</div>
