@props([
    'title',
    'eyebrow' => null,
    'percent' => null,
    'summary' => null,
    'route' => null,
    'routeLabel' => 'View all',
    'colSpan' => '',
])

<section @class(['rounded-lg border border-slate-200 bg-white p-5 shadow-sm', $colSpan])>
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            @if ($eyebrow)
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">{{ $eyebrow }}</p>
            @endif
            <h3 @class(['font-semibold text-gw-primary', $eyebrow ? 'mt-1 text-lg' : 'text-base'])>{{ $title }}</h3>
            @if ($summary)
                <p class="mt-1 text-xs text-slate-500">{{ $summary }}</p>
            @endif
        </div>

        @if ($route)
            <a
                href="{{ route($route) }}"
                class="shrink-0 rounded-full border border-gw-accent/50 px-3 py-1 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent/10"
            >
                {{ $routeLabel }}
            </a>
        @endif
    </div>

    @if (! is_null($percent))
        <div class="mb-4">
            <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-600">
                <span>Progress</span>
                <span class="text-gw-primary">{{ $percent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-slate-100">
                <div class="h-2 rounded-full bg-gw-accent" style="width: {{ $percent }}%"></div>
            </div>
        </div>
    @endif

    {{ $slot }}
</section>
