@props(['segment', 'compact' => false, 'modal' => false])

@php
    $event = $segment['event'];
    $color = $event->display_color;
    $spanPosition = $segment['span_position'] ?? 'single';
    $timeLabel = $segment['time_label'] ?? ($event->is_all_day ? 'All day' : $event->starts_at->format('g:i A'));
    $title = $event->title;
    $tooltip = $title.' · '.$timeLabel;
    $url = route('calendar.events.show', $event);

    $barHeight = $compact ? 'min-h-[2.125rem]' : 'min-h-[2.75rem]';
    $bg = $color.'1F';

    $shellClasses = match ($spanPosition) {
        'start' => 'relative z-10 -mr-[calc(0.5rem+1px)] overflow-hidden rounded-l-md shadow-sm',
        'middle' => 'relative z-10 -mx-[calc(0.5rem+1px)]',
        'end' => 'relative z-10 -ml-[calc(0.5rem+1px)] overflow-hidden rounded-r-md shadow-sm',
        default => 'overflow-hidden rounded-md shadow-sm',
    };
@endphp

@if ($spanPosition === 'middle')
    @if ($modal)
        <button
            type="button"
            wire:click.stop="openEventDetail({{ $event->id }})"
            {{ $attributes->merge(['class' => $barHeight.' block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }};"
            title="{{ $tooltip }}"
            aria-label="{{ $tooltip }}"
        ></button>
    @else
        <a
            href="{{ $url }}"
            {{ $attributes->merge(['class' => $barHeight.' block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }};"
            title="{{ $tooltip }}"
            aria-label="{{ $tooltip }}"
        ></a>
    @endif
@elseif ($spanPosition === 'end')
    @if ($modal)
        <button
            type="button"
            wire:click.stop="openEventDetail({{ $event->id }})"
            {{ $attributes->merge(['class' => $barHeight.' block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }}; box-shadow: inset 0 1px 0 {{ $color }}22;"
            title="{{ $tooltip }}"
            aria-label="{{ $tooltip }}"
        ></button>
    @else
        <a
            href="{{ $url }}"
            {{ $attributes->merge(['class' => $barHeight.' block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }}; box-shadow: inset 0 1px 0 {{ $color }}22;"
            title="{{ $tooltip }}"
            aria-label="{{ $tooltip }}"
        ></a>
    @endif
@else
    @if ($modal)
        <button
            type="button"
            wire:click.stop="openEventDetail({{ $event->id }})"
            {{ $attributes->merge(['class' => 'block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }}; box-shadow: inset 0 1px 0 {{ $color }}22, 0 1px 2px {{ $color }}14;"
            title="{{ $tooltip }}"
        >
            <div class="flex {{ $barHeight }}">
                <div class="w-1 shrink-0" style="background-color: {{ $color }};"></div>
                <div class="flex min-w-0 flex-1 flex-col justify-center py-0.5 pl-2 pr-2">
                    <div class="flex min-w-0 items-center gap-1.5">
                        <span class="truncate text-xs font-semibold leading-tight text-gw-primary">{{ $title }}</span>
                    </div>
                    <p class="truncate text-[0.65rem] font-medium leading-tight" style="color: {{ $color }};">
                        {{ $timeLabel }}
                    </p>
                </div>
            </div>
        </button>
    @else
        <a
            href="{{ $url }}"
            {{ $attributes->merge(['class' => 'block w-full text-left '.$shellClasses]) }}
            style="background-color: {{ $bg }}; box-shadow: inset 0 1px 0 {{ $color }}22, 0 1px 2px {{ $color }}14;"
            title="{{ $tooltip }}"
        >
            <div class="flex {{ $barHeight }}">
                <div class="w-1 shrink-0" style="background-color: {{ $color }};"></div>
                <div class="flex min-w-0 flex-1 flex-col justify-center py-0.5 pl-2 pr-2">
                    <div class="flex min-w-0 items-center gap-1.5">
                        <span class="truncate text-xs font-semibold leading-tight text-gw-primary">{{ $title }}</span>
                    </div>
                    <p class="truncate text-[0.65rem] font-medium leading-tight" style="color: {{ $color }};">
                        {{ $timeLabel }}
                    </p>
                </div>
            </div>
        </a>
    @endif
@endif
