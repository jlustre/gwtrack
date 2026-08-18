<x-app-layout>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Notifications</p>
                <h1 class="text-2xl font-semibold text-gw-primary">All notifications</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Personal alerts for mentorship, apprenticeship, training, assessments, announcements, events, and rank progress.
                </p>
            </div>

            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-gw-accent hover:bg-gw-accent-soft">
                    Mark All Read
                </button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Unread', 'value' => $unreadCount, 'theme' => 'gold'],
                ['label' => 'Read', 'value' => $readCount, 'theme' => 'navy'],
                ['label' => 'Total', 'value' => $notifications->total(), 'theme' => 'emerald'],
                ['label' => 'Types', 'value' => $typeCounts->count(), 'theme' => 'cyan'],
            ] as $stat)
                <x-tracker-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :theme="$stat['theme']"
                />
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_21rem]">
            <section class="overflow-hidden rounded-lg border border-slate-400 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gw-primary">Recent Alerts</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($notifications as $notification)
                        @php
                            $category = data_get($notification->data, 'category', 'General');
                            $tone = match (strtolower($category)) {
                                'mentorship', 'mentor assignment' => 'bg-gw-accent',
                                'training' => 'bg-emerald-500',
                                'licensing' => 'bg-red-500',
                                'event', 'events' => 'bg-sky-500',
                                'rank advancement' => 'bg-purple-500',
                                'announcement', 'announcements' => 'bg-orange-500',
                                default => 'bg-gw-primary',
                            };
                            $actionUrl = data_get($notification->data, 'action_url')
                                ?? data_get($notification->data, 'action_link.url')
                                ?? data_get($notification->data, 'action_link');
                        @endphp

                        <x-notification-item
                            :notification="$notification"
                            :tone="$tone"
                            :action-url="$actionUrl"
                            variant="page"
                            class="px-5 py-4"
                        />
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">
                            No notifications yet.
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $notifications->links() }}
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-gw-primary">Notification Types</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($typeCounts as $type => $count)
                            <div class="flex items-center justify-between rounded-md bg-white px-3 py-2 text-sm">
                                <span class="font-medium text-slate-700">{{ $type }}</span>
                                <span class="rounded-full border border-gw-accent bg-gw-accent-muted px-2 py-0.5 text-xs font-bold text-gw-primary">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Notification categories will appear here.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-gw-accent/30 bg-gw-primary p-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Live Center</p>
                    <h2 class="mt-2 text-lg font-semibold">Database Notifications</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        This page now reads real Laravel database notifications and supports read/unread controls.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
