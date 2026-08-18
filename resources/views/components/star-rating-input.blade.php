@props([
    'name',
    'value' => null,
    'max' => 5,
    'allowZero' => false,
    'size' => 'md',
])

@php
    $current = $value === null || $value === '' ? null : (int) $value;
    $starSize = match ($size) {
        'lg' => 'h-9 w-9',
        'sm' => 'h-6 w-6',
        default => 'h-8 w-8',
    };
    $labels = config('cfm-effectiveness.star_labels', []);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}>
    <div class="flex flex-wrap items-center gap-1.5">
        @if ($allowZero)
            <button
                type="button"
                wire:click="$set('{{ $name }}', 0)"
                class="mr-1 rounded-md border px-2 py-1 text-[11px] font-bold uppercase tracking-wide transition {{ $current === 0 ? 'border-slate-500 bg-slate-600 text-white' : 'border-slate-300 bg-white text-slate-500 hover:border-slate-400' }}"
                title="0 stars"
            >
                0
            </button>
        @endif

        @for ($star = 1; $star <= $max; $star++)
            <button
                type="button"
                wire:click="$set('{{ $name }}', {{ $star }})"
                class="group rounded-full p-0.5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gw-accent"
                title="{{ $star }} star{{ $star === 1 ? '' : 's' }} — {{ $labels[$star] ?? '' }}"
            >
                <svg
                    class="{{ $starSize }} transition {{ $current !== null && $current >= $star ? 'text-gw-accent drop-shadow-sm' : 'text-slate-300 group-hover:text-gw-accent/70' }}"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z" />
                </svg>
            </button>
        @endfor

        <span class="ml-2 text-sm font-semibold text-gw-primary">
            @if ($current === null)
                Not rated
            @else
                {{ $current }}/{{ $max }}
                <span class="font-medium text-slate-500">· {{ $labels[$current] ?? '' }}</span>
            @endif
        </span>
    </div>
</div>
