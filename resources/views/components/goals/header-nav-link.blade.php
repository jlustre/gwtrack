@props([
    'href' => null,
    'label',
    'tooltip',
    'variant' => 'secondary',
    'tab' => null,
    'active' => false,
])

@php
    $baseClass = $variant === 'primary'
        ? 'inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition'
        : 'inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition';

    if ($active) {
        $linkClass = $variant === 'primary'
            ? $baseClass.' border-gw-accent bg-gw-accent text-gw-primary ring-2 ring-white/40'
            : $baseClass.' border-white bg-white text-gw-primary';
    } else {
        $linkClass = $variant === 'primary'
            ? $baseClass.' border-gw-accent bg-gw-accent text-gw-primary hover:bg-gw-accent-hover'
            : $baseClass.' border-white/30 text-white hover:bg-white/10';
    }
@endphp

<span {{ $attributes->merge(['class' => 'group relative inline-flex']) }}>
    @if (filled($tab))
        <button
            type="button"
            wire:click="setTab('{{ $tab }}')"
            class="{{ $linkClass }}"
            @if ($active) aria-current="page" @endif
        >
            {{ $label }}
        </button>
    @else
        <a href="{{ $href }}" wire:navigate class="{{ $linkClass }}" @if ($active) aria-current="page" @endif>{{ $label }}</a>
    @endif
    <span
        role="tooltip"
        class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 w-64 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-lg border border-gw-accent bg-gw-accent-soft px-3 py-2.5 text-left text-xs font-medium leading-5 text-gw-primary opacity-0 shadow-[0_8px_24px_rgba(45, 62, 80,0.28)] ring-1 ring-gw-primary/15 transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
    >{{ $tooltip }}</span>
</span>
