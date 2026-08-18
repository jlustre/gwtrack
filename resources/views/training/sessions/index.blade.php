<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.coaching.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; FAP & Coaching</a>
                    <h1 class="mt-2 text-3xl font-semibold">Live Training Sessions</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">Register for academy sessions and sync them to your GWTrack calendar.</p>
                </div>
                <a href="{{ route('calendar.index') }}" class="inline-flex rounded-md border border-gw-accent/50 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-accent/20">
                    Open calendar
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-3">
                @forelse ($rows as $row)
                    @php $session = $row['session']; @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <a href="{{ route('training.sessions.show', $session) }}" class="font-semibold text-gw-primary transition hover:text-gw-accent">{{ $session->title }}</a>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $session->starts_at?->format('M j, Y g:i A') }}
                                    · {{ config('training-academy.coaching.session_types.'.$session->session_type, $session->session_type) }}
                                </p>
                                @if ($session->instructor)
                                    <p class="mt-1 text-xs text-slate-500">Led by {{ $session->instructor->name }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($row['attended'])
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-emerald-800">Attended</span>
                                @elseif ($row['registered'])
                                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase text-sky-800">Registered</span>
                                @endif
                                @if ($row['calendar_url'])
                                    <a href="{{ $row['calendar_url'] }}" class="inline-flex rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-white">Calendar</a>
                                @endif
                                <a href="{{ route('training.sessions.show', $session) }}" class="inline-flex rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover">Details</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No upcoming sessions scheduled.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
