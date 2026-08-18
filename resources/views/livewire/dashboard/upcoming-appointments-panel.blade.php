<section @class(['rounded-xl border border-slate-200 bg-white shadow-sm', 'p-4' => $compact, 'p-5' => ! $compact])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming Appointments</h2>
            <p class="mt-1 text-sm text-slate-600">
                @if ($appointments->isNotEmpty())
                    {{ $appointments->count() }} scheduled prospect appointment{{ $appointments->count() === 1 ? '' : 's' }}
                @else
                    CRM appointments synced to your calendar.
                @endif
            </p>
        </div>
        @can('manage prospects')
            <a href="{{ route('team.prospects.appointments') }}" wire:navigate class="rounded-md bg-gradient-to-br from-gw-primary via-[#1E3A8A] to-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white hover:brightness-110">
                Manage
            </a>
        @endcan
    </div>

    @if ($appointments->isEmpty())
        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
            No upcoming appointments scheduled.
        </div>
    @else
        <div @class(['mt-4 space-y-2', 'max-h-80 overflow-y-auto pr-1' => $compact])>
            @foreach ($appointments as $appointment)
                <div wire:key="upcoming-appointment-{{ $appointment->id }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            @if ($appointment->prospect)
                                <a href="{{ route('team.prospects.records.show', $appointment->prospect) }}" wire:navigate class="text-sm font-semibold text-gw-primary hover:text-[#1D4ED8]">
                                    {{ $appointment->prospect->displayName() }}
                                </a>
                            @else
                                <p class="text-sm font-semibold text-gw-primary">Prospect appointment</p>
                            @endif
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $appointment->type?->name ?? 'Appointment' }} · {{ $appointment->scheduled_at->format('M j, g:i A') }}
                            </p>
                            @if ($appointment->purpose)
                                <p class="mt-1 text-xs text-slate-600">{{ $appointment->purpose }}</p>
                            @endif
                        </div>
                        @if ($appointment->calendar_event_id)
                            <a href="{{ route('calendar.events.show', $appointment->calendar_event_id) }}" wire:navigate class="shrink-0 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">
                                Calendar
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
