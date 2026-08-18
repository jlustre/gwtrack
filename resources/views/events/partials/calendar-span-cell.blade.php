@props(['item' => null, 'compact' => false, 'showOwner' => false, 'modal' => false])

@if ($item)
    @if ($item['type'] === 'block')
        @include('events.partials.schedule-block-chip', [
            'block' => $item['payload'],
            'compact' => $compact,
            'showOwner' => $showOwner,
        ])
    @else
        @include('events.partials.calendar-event-chip', [
            'segment' => $item['payload'],
            'compact' => $compact,
            'modal' => $modal,
        ])
    @endif
@else
    <div class="{{ $compact ? 'min-h-[2.125rem]' : 'min-h-[2.75rem]' }}" aria-hidden="true"></div>
@endif
