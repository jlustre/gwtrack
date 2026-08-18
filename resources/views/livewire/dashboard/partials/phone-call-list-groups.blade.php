@php
    $today = $panels['today'] ?? collect();
    $tomorrow = $panels['tomorrow'] ?? collect();
    $later = $panels['later'] ?? collect();
    $followUp = $panels['follow_up'] ?? collect();
    $stats = $panels['stats'] ?? ['total' => 0, 'today' => 0, 'tomorrow' => 0, 'follow_up' => 0, 'active' => 0];
    $mode = $mode ?? 'modal';
    $excludeCallId = $excludeCallId ?? null;
    $followUpItems = $excludeCallId
        ? $followUp->reject(fn ($call) => $call->id === $excludeCallId)->values()
        : $followUp;
@endphp

@foreach ([['label' => 'Today', 'items' => $today], ['label' => 'Tomorrow', 'items' => $tomorrow], ['label' => 'Later', 'items' => $later]] as $group)
    @if ($group['items']->isNotEmpty())
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $group['label'] }}</p>
            <div class="mt-2 space-y-2">
                @foreach ($group['items'] as $call)
                    @include('livewire.dashboard.partials.phone-call-list-item', ['call' => $call, 'mode' => $mode])
                @endforeach
            </div>
        </div>
    @endif
@endforeach

@if ($followUpItems->isNotEmpty())
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Follow up</p>
        <p class="mt-0.5 text-xs text-slate-500">Past-due calls stay here until you complete them, reschedule, or delete them.</p>
        <div class="mt-2 space-y-2">
            @foreach ($followUpItems as $call)
                @include('livewire.dashboard.partials.phone-call-list-item', ['call' => $call, 'mode' => $mode, 'overdue' => true])
            @endforeach
        </div>
    </div>
@endif
