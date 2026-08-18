@props([
    'text' => null,
    'examples' => [],
    'placement' => 'right',
])

@php
    $examples = collect($examples)->filter(fn ($example) => filled($example))->values();
    $tooltipPosition = $placement === 'left'
        ? 'right-full top-1/2 mr-2 -translate-y-1/2'
        : 'left-full top-1/2 ml-2 -translate-y-1/2';
@endphp

@if (filled($text) || $examples->isNotEmpty())
    <span
        {{ $attributes->merge([
            'class' => 'group relative inline-flex h-6 w-6 shrink-0 cursor-help items-center justify-center rounded-full border border-gw-accent/35 bg-gw-accent-soft text-xs font-bold text-gw-accent-dark shadow-sm focus:outline-none focus:ring-2 focus:ring-gw-accent/50',
            'tabindex' => '0',
            'role' => 'button',
            'aria-label' => 'More information',
        ]) }}
    >
        ?
        <span @class([
            'pointer-events-none absolute z-[210] hidden w-80 rounded-md bg-gw-primary px-3 py-2.5 text-left text-xs font-medium leading-5 text-white shadow-lg group-hover:block group-focus:block group-focus-within:block',
            $tooltipPosition,
        ])>
            @if (filled($text))
                <span class="block">{{ $text }}</span>
            @endif

            @if ($examples->isNotEmpty())
                <span @class(['mt-2 block border-t border-white/20 pt-2' => filled($text)])>
                    <span class="font-semibold text-gw-accent">Examples</span>
                    <ul class="mt-1 list-disc space-y-1 pl-4 font-normal text-slate-100">
                        @foreach ($examples as $example)
                            <li>{{ $example }}</li>
                        @endforeach
                    </ul>
                </span>
            @endif
        </span>
    </span>
@endif
