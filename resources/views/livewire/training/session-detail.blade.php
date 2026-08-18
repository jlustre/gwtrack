<div class="space-y-6">
    @if (session('session_status') === 'registered')
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800">Registered. This session has been added to your calendar.</div>
    @elseif (session('session_status') === 'checked-in')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Attendance recorded.</div>
    @elseif (session('session_status') === 'attendance-updated')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Attendance updated.</div>
    @endif

    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('training.sessions.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Live sessions</a>
                <h1 class="mt-2 text-3xl font-semibold">{{ $session->title }}</h1>
                <p class="mt-2 text-sm text-slate-300">
                    {{ $session->starts_at?->format('l, M j, Y g:i A') }}
                    @if ($session->ends_at) – {{ $session->ends_at->format('g:i A') }} @endif
                </p>
                <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ config('training-academy.coaching.session_types.'.$session->session_type, $session->session_type) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($calendarUrl)
                    <a href="{{ $calendarUrl }}" class="inline-flex rounded-md border border-gw-accent/50 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-accent/20">
                        View in calendar
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            @if ($session->description)
                <p class="text-sm leading-6 text-slate-600">{{ $session->description }}</p>
            @endif
            <dl class="mt-5 space-y-3 text-sm">
                @if ($session->instructor)
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Instructor</dt>
                        <dd class="font-semibold text-gw-primary">{{ $session->instructor->name }}</dd>
                    </div>
                @endif
                @if ($session->module)
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Linked course</dt>
                        <dd><a href="{{ route('training.courses.show', $session->module) }}" class="font-semibold text-gw-primary underline">{{ $session->module->title }}</a></dd>
                    </div>
                @endif
                @if ($session->capacity)
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Capacity</dt>
                        <dd class="font-semibold text-gw-primary">{{ $session->attendance->count() }} / {{ $session->capacity }}</dd>
                    </div>
                @endif
            </dl>

            @if ($canManage)
                <div class="mt-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Attendance roster</h2>
                    <div class="mt-3 space-y-2">
                        @forelse ($session->attendance as $record)
                            <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                                <span class="font-medium text-gw-primary">{{ $record->user?->name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">{{ str($record->status)->title() }}</span>
                                    @if ($record->status !== 'attended')
                                        <button type="button" wire:click="markAttended({{ $record->id }})" class="text-xs font-semibold text-emerald-700 underline">Mark attended</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-600">No registrations yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @if (! $userAttendance)
                <button type="button" wire:click="register" class="flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-3 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                    Register for session
                </button>
            @elseif ($userAttendance->status !== 'attended')
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-gw-primary">You are registered</p>
                    <button type="button" wire:click="checkIn" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                        Check in
                    </button>
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-sm font-semibold text-emerald-900">You are checked in for this session.</p>
                </div>
            @endif
        </div>
    </div>
</div>
