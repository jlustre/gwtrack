@props([
    'card',
    'theme',
    'subtitle' => null,
    'context' => 'team',
    'url' => null,
    'showBar' => true,
])

@php
    $bar = dashboardStatBarClasses($theme);
    $cardTheme = dashboardStatCardTheme($card['key'], $context);
    $buttonClasses = $cardTheme['button'] ?? 'border-slate-200 bg-white/90 text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft';
@endphp

<div class="relative h-full">
    @if ($url)
        <a
            href="{{ $url }}"
            class="block h-full rounded-lg transition hover:scale-[1.01] focus:outline-none focus-visible:ring-2 focus-visible:ring-gw-accent"
        >
    @endif

    <x-tracker-stat-card
        :label="$card['label']"
        :value="$card['value']"
        :theme="$theme"
        :subtitle="$subtitle"
        :bar="$showBar ? ($card['bar'] ?? 0) : null"
        :bar-track="$bar['track']"
        :bar-fill="$bar['fill']"
        :reserve-bar-space="! $showBar"
        class="h-full"
    />

    @if ($url)
        </a>
    @endif

    <button
        type="button"
        class="absolute bottom-2 right-2 z-10 inline-flex h-7 w-7 items-center justify-center rounded-full border shadow-sm backdrop-blur-sm transition {{ $buttonClasses }}"
        x-on:click.stop="openModal(@js($card['key']), @js($card['label']), @js($context))"
        aria-label="View {{ $card['label'] }} details"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
        </svg>
    </button>
</div>
