<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 text-lg font-semibold text-gw-primary">Recent Activity</h3>

    @if (count($profile['activityTimeline'] ?? []) > 0)
        <ul class="mb-5 space-y-2 text-sm">
            @foreach ($profile['activityTimeline'] as $item)
                <li class="flex justify-between gap-4 border-b border-slate-200 pb-2">
                    <span class="text-slate-700">{{ $item['label'] }}</span>
                    <span class="whitespace-nowrap text-xs text-slate-500">{{ $item['time'] }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mb-5 text-sm text-slate-500">No recent mentor activity recorded.</p>
    @endif

    <h4 class="mb-2 text-sm font-semibold text-gw-accent-dark">Assignment History</h4>
    @if (count($profile['assignmentHistory'] ?? []) > 0)
        <ul class="space-y-1 text-xs text-slate-600">
            @foreach ($profile['assignmentHistory'] as $history)
                <li>{{ $history['apprentice'] }} — {{ $history['status'] }} ({{ $history['date'] }})</li>
            @endforeach
        </ul>
    @else
        <p class="text-xs text-slate-500">No assignment history yet.</p>
    @endif
</div>
