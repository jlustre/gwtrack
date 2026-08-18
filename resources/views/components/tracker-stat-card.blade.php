@props([
    'label',
    'value' => null,
    'subtitle' => null,
    'badge' => null,
    'theme' => 'gold',
    'bar' => null,
    'barTrack' => null,
    'barFill' => null,
    'reserveBarSpace' => false,
])

@php
    $themes = [
        'gold' => [
            'article' => 'border-gw-accent/35 bg-gradient-to-br from-gw-accent-soft via-[#FFF0C9] to-[#F3D98A]',
            'label' => 'text-gw-accent-dark',
            'value' => 'text-gw-primary',
            'subtitle' => 'text-[#5C4A14]',
            'badge' => 'bg-gw-primary/10 text-gw-primary',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-gw-accent/25 blur-sm" aria-hidden="true"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-10 w-full bg-gradient-to-t from-gw-accent/10 to-transparent" aria-hidden="true"></div>
            HTML,
        ],
        'navy' => [
            'article' => 'border-gw-primary/15 bg-gradient-to-br from-[#E9EEF5] via-[#D5DEEA] to-[#B8C8DA]',
            'label' => 'text-gw-primary/70',
            'value' => 'text-gw-primary',
            'subtitle' => 'text-[#334155]',
            'badge' => 'bg-gw-primary/10 text-gw-primary',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute -left-3 top-1/2 h-14 w-14 -translate-y-1/2 rounded-full bg-gw-primary/10" aria-hidden="true"></div>
            HTML,
        ],
        'cyan' => [
            'article' => 'border-cyan-200/80 bg-gradient-to-br from-cyan-50 via-sky-50 to-teal-100',
            'label' => 'text-cyan-800/70',
            'value' => 'text-gw-primary',
            'subtitle' => 'text-cyan-900/70',
            'badge' => 'bg-cyan-900/10 text-cyan-950',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute -right-2 bottom-0 h-12 w-12 rounded-tl-full bg-cyan-300/30" aria-hidden="true"></div>
            HTML,
        ],
        'amber' => [
            'article' => 'border-amber-200/90 bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100',
            'label' => 'text-amber-800/80',
            'value' => 'text-amber-950',
            'subtitle' => 'text-amber-900/70',
            'badge' => 'bg-amber-900/10 text-amber-950',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute right-2 top-2 h-8 w-8 rounded-full border-2 border-amber-300/50" aria-hidden="true"></div>
            HTML,
        ],
        'violet' => [
            'article' => 'border-violet-200/90 bg-gradient-to-br from-violet-50 via-purple-50 to-fuchsia-100',
            'label' => 'text-violet-800/80',
            'value' => 'text-violet-950',
            'subtitle' => 'text-violet-900/70',
            'badge' => 'bg-violet-900/10 text-violet-950',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute -bottom-3 -right-3 h-16 w-16 rounded-full bg-violet-400/20" aria-hidden="true"></div>
            HTML,
        ],
        'slate' => [
            'article' => 'border-slate-200 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200',
            'label' => 'text-slate-600',
            'value' => 'text-gw-primary',
            'subtitle' => 'text-slate-600',
            'badge' => 'bg-slate-700/10 text-slate-800',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-slate-400/40 to-transparent" aria-hidden="true"></div>
            HTML,
        ],
        'emerald' => [
            'article' => 'border-emerald-200/90 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-100',
            'label' => 'text-emerald-800/80',
            'value' => 'text-emerald-950',
            'subtitle' => 'text-emerald-900/70',
            'badge' => 'bg-emerald-900/10 text-emerald-950',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute -right-3 bottom-2 h-10 w-10 rounded-full bg-emerald-400/25" aria-hidden="true"></div>
            HTML,
        ],
        'red' => [
            'article' => 'border-red-200/90 bg-gradient-to-br from-red-50 via-rose-50 to-red-100',
            'label' => 'text-red-800/80',
            'value' => 'text-red-950',
            'subtitle' => 'text-red-900/70',
            'badge' => 'bg-red-900/10 text-red-950',
            'decorations' => <<<'HTML'
                <div class="pointer-events-none absolute right-2 top-2 h-8 w-8 rounded-full border-2 border-red-300/50" aria-hidden="true"></div>
            HTML,
        ],
    ];

    $style = $themes[$theme] ?? $themes['gold'];
    $uniformHeight = $reserveBarSpace || ! is_null($bar);
@endphp

<article {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-lg border p-3 shadow-sm '.$style['article'].($uniformHeight ? ' flex h-full min-h-[8.75rem] flex-col' : '')]) }}>
    {!! $style['decorations'] !!}
    <div @class([
        'relative min-w-0' => ! $uniformHeight,
        'relative flex min-h-0 flex-1 flex-col' => $uniformHeight && ! filled($badge),
        'relative flex min-h-0 flex-1 items-start justify-between gap-2' => $uniformHeight && filled($badge),
        'relative flex items-start justify-between gap-2' => ! $uniformHeight && filled($badge),
    ])>
        <div @class(['min-w-0', 'min-w-0 flex-1' => $uniformHeight])>
            <p class="text-[10px] font-bold uppercase tracking-wider {{ $style['label'] }}">{{ $label }}</p>
            <p class="mt-1 text-xl font-bold tabular-nums leading-none {{ $style['value'] }}">
                @if (! is_null($value) && $value !== '')
                    {{ $value }}
                @else
                    {{ $slot }}
                @endif
            </p>
            @if ($subtitle)
                <p class="mt-1 line-clamp-2 text-[11px] leading-tight {{ $style['subtitle'] }}">{{ $subtitle }}</p>
            @elseif ($reserveBarSpace)
                <p class="mt-1 text-[11px] leading-tight opacity-0" aria-hidden="true">&nbsp;</p>
            @endif
        </div>
        @if ($badge)
            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums {{ $style['badge'] }}">{{ $badge }}</span>
        @endif
    </div>

    @if (! is_null($bar))
        <div class="relative mt-3 shrink-0 pr-8">
            <div @class(['h-2 rounded-full', $barTrack ?? 'bg-slate-200'])>
                <div
                    @class(['h-2 rounded-full transition-all duration-300', $barFill ?? 'bg-slate-600'])
                    style="width: {{ max(0, min(100, (int) $bar)) }}%"
                ></div>
            </div>
        </div>
    @elseif ($reserveBarSpace)
        <div class="relative mt-3 h-2 shrink-0 pr-8" aria-hidden="true"></div>
    @endif
</article>
