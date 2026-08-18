@php
    $today = $panels['today'] ?? collect();
    $tomorrow = $panels['tomorrow'] ?? collect();
    $later = $panels['later'] ?? collect();
    $followUp = $panels['follow_up'] ?? collect();
    $stats = $panels['stats'] ?? ['total' => 0, 'today' => 0, 'tomorrow' => 0, 'follow_up' => 0, 'active' => 0];
    $hasCalls = ($stats['total'] ?? 0) > 0;
@endphp

<section @class(['rounded-xl border border-slate-200 bg-white shadow-sm', 'p-4' => $compact, 'p-5' => ! $compact])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming Phone Calls</h2>
            <p class="mt-1 text-sm text-slate-600">
                @if ($hasCalls)
                    {{ $stats['today'] ?? 0 }} today · {{ $stats['tomorrow'] ?? 0 }} tomorrow{{ (($stats['follow_up'] ?? 0) > 0) ? ' · '.$stats['follow_up'].' follow up' : '' }}
                @else
                    Schedule calls from Quick Actions to build your calling list.
                @endif
            </p>
        </div>
        <button type="button" onclick="Livewire.dispatch('open-dashboard-schedule-phone-call-modal')" class="rounded-md bg-gradient-to-br from-[#1E3A8A] via-[#1D4ED8] to-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white hover:brightness-110">
            Schedule call
        </button>
    </div>

    @if (session('phone_calls_status'))
        <div class="mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">
            {{ session('phone_calls_status') }}
        </div>
    @endif

    @if (! $hasCalls)
        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
            No upcoming phone calls yet.
        </div>
    @else
        <div @class(['mt-4 space-y-4', 'max-h-80 overflow-y-auto pr-1' => $compact])>
            @include('livewire.dashboard.partials.phone-call-list-groups', ['panels' => $panels, 'mode' => 'panel'])
        </div>
    @endif
</section>
