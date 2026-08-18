@props(['block', 'compact' => false, 'showOwner' => false])

@php
    $color = $block['color'] ?? '#64748B';
    $spanPosition = $block['span_position'] ?? 'single';
    $rangeStart = $block['range_starts_at'] ?? $block['starts_at'];
    $rangeEnd = $block['range_ends_at'] ?? $block['ends_at'];

    $timeLabel = $block['time_label'] ?? (
        ($block['range_is_all_day'] ?? $block['is_all_day'] ?? false)
            ? ($rangeStart->isSameDay($rangeEnd) ? 'All day' : 'All day · '.$rangeStart->format('M j').' – '.$rangeEnd->format('M j'))
            : ($rangeStart->isSameDay($rangeEnd)
                ? $rangeStart->format('g:i A').' – '.$rangeEnd->format('g:i A')
                : $rangeStart->format('D g:i A').' → '.$rangeEnd->format('D g:i A'))
    );

    $title = $block['label'] ?? 'Blocked';
    $tooltip = $title.' · '.$timeLabel.($showOwner && ! empty($block['owner_name']) ? ' · '.$block['owner_name'] : '');

    $barHeight = $compact ? 'min-h-[2.125rem]' : 'min-h-[2.75rem]';
    $bg = $color.'1F';

    $shellClasses = match ($spanPosition) {
        'start' => 'relative z-10 -mr-2 overflow-hidden rounded-l-md shadow-sm',
        'middle' => 'relative z-10 -mx-2',
        'end' => 'relative z-10 -ml-2 overflow-hidden rounded-r-md shadow-sm',
        default => 'overflow-hidden rounded-md shadow-sm',
    };
@endphp

@if ($spanPosition === 'middle')
    <div
        {{ $attributes->merge(['class' => $barHeight.' '.$shellClasses]) }}
        style="background-color: {{ $bg }};"
        title="{{ $tooltip }}"
        aria-hidden="true"
    ></div>
@elseif ($spanPosition === 'end')
    <div
        {{ $attributes->merge(['class' => $barHeight.' '.$shellClasses]) }}
        style="background-color: {{ $bg }}; box-shadow: inset 0 1px 0 {{ $color }}22;"
        title="{{ $tooltip }}"
        aria-hidden="true"
    ></div>
@else
    <div
        {{ $attributes->merge(['class' => $shellClasses]) }}
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
                    @if ($showOwner && ! empty($block['owner_name']))
                        <span class="text-slate-500"> · {{ $block['owner_name'] }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
