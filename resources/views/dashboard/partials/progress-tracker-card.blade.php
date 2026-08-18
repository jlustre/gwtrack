@php
    $theme = $theme ?? [];
    $percent = (int) ($percent ?? 0);
    $restricted = (bool) ($restricted ?? false);
@endphp

<article @class([
    'flex h-full flex-col rounded-lg border bg-white p-5 shadow-sm',
    $restricted ? 'border-dashed border-slate-200 bg-slate-50' : 'border-slate-200',
])>
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-gw-primary">{{ $tracker['label'] ?? 'Progress' }}</h3>
        <p class="mt-1 text-xs text-slate-500">{{ $tracker['summary'] ?? '' }}</p>
    </div>

    @if (! $restricted)
        <div class="mt-auto">
            <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-600">
                <span>Completion</span>
                <span class="{{ $theme['accent'] ?? 'text-gw-accent-dark' }}">{{ $percent }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full {{ $theme['track'] ?? 'bg-slate-100' }}">
                <div class="h-2 rounded-full {{ $theme['fill'] ?? 'bg-gw-accent' }} transition-all duration-300" style="width: {{ max(0, min(100, $percent)) }}%"></div>
            </div>
        </div>
    @endif
</article>
