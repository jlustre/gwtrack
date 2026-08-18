@props([
    'eyebrow' => 'FNA Management',
    'title',
    'description' => null,
])

<div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
    <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">{{ $eyebrow }}</p>
            <h1 class="mt-2 text-2xl font-semibold">{{ $title }}</h1>
            @if ($description)
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-200">{{ $description }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex flex-wrap gap-2">{!! $actions !!}</div>
        @endif
    </div>
    @if (isset($body))
        <div class="p-6">{{ $body }}</div>
    @endif
</div>
