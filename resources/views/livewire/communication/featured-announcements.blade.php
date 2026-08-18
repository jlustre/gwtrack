@if ($featured->isNotEmpty() || $unreadCount > 0)
    <div class="space-y-3">
        @if ($unreadCount > 0)
            <a
                href="{{ route('communications.index', ['unreadOnly' => 1]) }}"
                class="flex items-center justify-between rounded-lg border border-gw-accent/30 bg-gw-accent-soft px-3 py-2 text-sm transition hover:border-gw-accent"
            >
                <span class="font-semibold text-gw-primary">{{ $unreadCount }} unread {{ Str::plural('announcement', $unreadCount) }}</span>
                <span class="text-xs font-semibold text-gw-accent-dark">View →</span>
            </a>
        @endif

        @foreach ($featured as $item)
            <a
                href="{{ route('communications.show', $item['slug']) }}"
                class="block rounded-lg border border-gw-primary/10 bg-gradient-to-r from-gw-primary to-[#132a4d] px-3 py-3 text-white transition hover:border-gw-accent/40"
            >
                @if (! empty($item['category']))
                    <span class="text-[0.65rem] font-semibold uppercase tracking-wide text-gw-accent">{{ $item['category'] }}</span>
                @endif
                <div class="mt-1 text-sm font-semibold">{{ $item['title'] }}</div>
                @if (! empty($item['summary']))
                    <p class="mt-1 line-clamp-2 text-xs text-slate-300">{{ $item['summary'] }}</p>
                @endif
            </a>
        @endforeach
    </div>
@endif
