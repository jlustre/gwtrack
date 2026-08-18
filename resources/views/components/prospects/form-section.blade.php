@props([
    'title',
    'description' => null,
    'innerClass' => null,
    'compact' => false,
])

@php
    $defaultInnerClass = $compact
        ? 'grid grid-cols-1 gap-1.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6'
        : 'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';

    $innerClass = $innerClass ?? $defaultInnerClass;

    $sectionClass = $compact
        ? 'rounded-md border border-slate-200 bg-white p-2 shadow-sm'
        : 'rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3';

    $headerClass = $compact
        ? 'mb-1.5 border-b border-slate-100 pb-1'
        : 'mb-2 border-b border-slate-100 pb-1.5';
@endphp

<section {{ $attributes->merge(['class' => $sectionClass]) }}>
    <header class="{{ $headerClass }}">
        <h3 class="text-[11px] font-bold uppercase tracking-wider text-gw-primary">{{ $title }}</h3>
        @if ($description)
            <p class="mt-0.5 text-[11px] leading-tight text-slate-500">{{ $description }}</p>
        @endif
    </header>
    <div class="{{ $innerClass }}">
        {{ $slot }}
    </div>
</section>
