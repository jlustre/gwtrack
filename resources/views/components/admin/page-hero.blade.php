@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'gw-page-hero relative overflow-hidden rounded-lg border border-gw-primary bg-gradient-to-br from-gw-primary via-[#243846] to-gw-primary px-6 py-7 text-white shadow-lg']) }}>
    <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(132,187,65,0.22),transparent_55%)]" aria-hidden="true"></div>
    <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $eyebrow }}</p>
            @endif
            @if ($title)
                <h1 @class(['text-2xl font-semibold text-white', 'mt-2' => filled($eyebrow)])>{{ $title }}</h1>
            @endif
            @if ($description)
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
