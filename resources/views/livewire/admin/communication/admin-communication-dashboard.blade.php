<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total announcements', 'value' => number_format($metrics['total_announcements']), 'theme' => 'navy'],
            ['label' => 'Active', 'value' => number_format($metrics['active_announcements']), 'theme' => 'gold'],
            ['label' => 'Critical live', 'value' => number_format($metrics['critical_announcements']), 'theme' => 'red'],
            ['label' => 'Pending acks', 'value' => number_format($metrics['pending_acknowledgements']), 'theme' => 'amber'],
            ['label' => 'Total views', 'value' => number_format($metrics['total_views']), 'theme' => 'cyan'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Reactions', 'value' => number_format($metrics['total_reactions']), 'theme' => 'violet'],
            ['label' => 'Comments', 'value' => number_format($metrics['total_comments']), 'theme' => 'emerald'],
            ['label' => 'Bookmarks', 'value' => number_format($metrics['total_bookmarks']), 'theme' => 'gold'],
            ['label' => 'Campaigns running', 'value' => number_format($metrics['campaigns_running']), 'theme' => 'slate'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Top announcements</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse ($topAnnouncements as $row)
                    <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                        <a href="{{ route('communications.show', $row['slug']) }}" class="font-medium text-gw-primary hover:text-gw-accent-dark">{{ $row['title'] }}</a>
                        <span class="shrink-0 text-xs text-slate-500">{{ number_format($row['views']) }} views · {{ number_format($row['engagement']) }} engaged</span>
                    </li>
                @empty
                    <li class="text-slate-500">No published announcements yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Engagement trend (14d)</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse ($trend as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span>{{ $row->stat_date->format('M j') }}</span>
                        <span class="font-semibold">{{ number_format($row->views) }} views · {{ number_format($row->reactions + $row->comments) }} interactions</span>
                    </li>
                @empty
                    <li class="text-slate-500">Daily rollups will appear after the analytics job runs.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Recent broadcasts</h2>
            @can('send broadcast')
                <a href="{{ route('admin.communications.broadcasts') }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">Send broadcast →</a>
            @endcan
        </div>
        <ul class="mt-4 space-y-2 text-sm text-slate-700">
            @forelse ($recentBroadcasts as $broadcast)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2">
                    <div>
                        <p class="font-medium text-gw-primary">{{ $broadcast->title }}</p>
                        <p class="text-xs text-slate-500">By {{ $broadcast->sender?->name ?? 'System' }} · {{ $broadcast->sent_at?->diffForHumans() }}</p>
                    </div>
                    <span class="text-xs font-semibold text-gw-accent-dark">{{ number_format($broadcast->recipient_count) }} recipients</span>
                </li>
            @empty
                <li class="text-slate-500">No broadcasts sent yet.</li>
            @endforelse
        </ul>
    </div>
</div>
