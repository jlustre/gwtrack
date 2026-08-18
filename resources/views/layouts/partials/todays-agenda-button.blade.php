@php
    $summary = $todaysAgendaSummary ?? [];
    $total = (int) ($summary['total'] ?? 0);
@endphp

<button
    type="button"
    class="gw-icon-btn-lg relative"
    title="{{ $total > 0 ? $total.' items for today' : 'Today\'s agenda' }}"
    onclick="Livewire.dispatch('open-todays-agenda-modal')"
>
    <span class="sr-only">Open today's agenda</span>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"></path>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h6"></path>
    </svg>
    @if ($total > 0)
        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0F766E] px-1 text-[0.65rem] font-bold text-white">
            {{ $total > 99 ? '99+' : $total }}
        </span>
    @endif
</button>
