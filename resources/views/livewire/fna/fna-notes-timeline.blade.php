<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-semibold text-gw-primary">Activity Timeline</h2>

    @if ($entries->isEmpty())
        <p class="mt-4 text-sm text-slate-600">No activity yet.</p>
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($entries as $entry)
                <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-3 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($entry['type'] === 'activity')
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700">Activity</span>
                        @elseif ($entry['type'] === 'review')
                            <span class="rounded-full bg-gw-accent/20 px-2 py-0.5 text-xs font-bold text-gw-accent-dark">Review</span>
                        @else
                            <span class="rounded-full bg-gw-primary/10 px-2 py-0.5 text-xs font-bold text-gw-primary">Status</span>
                        @endif
                        <span class="font-semibold text-gw-primary">{{ $entry['title'] }}</span>
                        <span class="text-xs text-slate-500">{{ $entry['at']->format('M j, Y g:i A') }}</span>
                    </div>
                    @if ($entry['body'])
                        <p class="mt-1 text-slate-700">{{ $entry['body'] }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">By {{ $entry['actor'] }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
