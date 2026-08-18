<div class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft shadow-sm">
    @if (session('tab_status'))
        <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('tab_status') }}
        </div>
    @endif

    @if (session('prospect_quick_log_status'))
        <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('prospect_quick_log_status') }}
        </div>
    @endif

    <div class="border-b border-slate-200 px-4 py-3">
        <div class="flex flex-wrap gap-2">
            @foreach (['timeline' => 'Timeline', 'activities' => 'Activities', 'communications' => 'Calls & Comms', 'calendar' => 'Calendar', 'notes' => 'Notes', 'fna' => 'FNA'] as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="rounded-lg px-3 py-1.5 text-sm font-semibold transition {{ $activeTab === $tab ? 'bg-gw-primary text-gw-accent' : 'bg-white text-gw-primary hover:bg-gw-accent-soft' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($canLogActivities)
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="openLogCall"
                    class="rounded-lg border border-gw-accent bg-gw-accent-muted px-3 py-1.5 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-soft"
                >
                    Log Call
                </button>
                <button
                    type="button"
                    wire:click="openLogActivity"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50"
                >
                    Log Activity
                </button>
                <button
                    type="button"
                    wire:click="openLogCommunication"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50"
                >
                    Log Communication
                </button>
            </div>
        @endif
    </div>

    <div class="p-6">
        @if ($activeTab === 'timeline')
            <livewire:prospects.prospect-timeline :prospect="$prospect" :key="'timeline-'.$prospect->id" />
        @endif

        @if ($activeTab === 'activities')
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-600">{{ $activities->count() }} logged {{ str('activity')->plural($activities->count()) }}</p>
                @if ($canLogActivities)
                    <button
                        type="button"
                        wire:click="openLogActivity"
                        class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white"
                    >
                        + Log Activity
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($activities as $activity)
                    @php
                        $typeLabel = $activityTypes[$activity->activity_type] ?? str($activity->activity_type)->replace('_', ' ')->title();
                    @endphp
                    <article class="rounded-lg border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-blue-800">{{ $typeLabel }}</span>
                                @if ($activity->outcome)
                                    <span class="text-sm font-semibold text-gw-primary">{{ $activity->outcome }}</span>
                                @endif
                            </div>
                            <time class="text-xs text-slate-500">{{ $activity->occurred_at?->format('M j, Y g:i A') }}</time>
                        </div>
                        @if ($activity->notes)
                            <p class="mt-2 text-sm text-slate-700">{{ $activity->notes }}</p>
                        @endif
                        @if ($activity->next_action || $activity->next_follow_up_at)
                            <div class="mt-3 rounded-lg border border-gw-accent/30 bg-gw-accent-soft px-3 py-2 text-sm">
                                @if ($activity->next_action)
                                    <p class="font-semibold text-gw-primary">Next: {{ $activity->next_action }}</p>
                                @endif
                                @if ($activity->next_follow_up_at)
                                    <p class="mt-1 text-xs text-slate-600">Follow-up {{ $activity->next_follow_up_at->format('M j, Y g:i A') }}</p>
                                @endif
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-slate-500">
                            Logged by {{ $activity->user?->name ?? 'Unknown' }}
                            @if ($activity->duration_minutes)
                                · {{ $activity->duration_minutes }} min
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-gw-primary">No activities yet</p>
                        <p class="mt-1 text-sm text-slate-500">Log phone calls, emails, presentations, and follow-ups for this prospect.</p>
                        @if ($canLogActivities)
                            <button
                                type="button"
                                wire:click="openLogCall"
                                class="mt-4 rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white"
                            >
                                Log first call
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        @endif

        @if ($activeTab === 'communications')
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-600">{{ $communications->count() }} logged {{ str('communication')->plural($communications->count()) }}</p>
                @if ($canLogActivities)
                    <button
                        type="button"
                        wire:click="openLogCommunication"
                        class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white"
                    >
                        + Log Communication
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($communications as $communication)
                    <article class="rounded-lg border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-800">
                                    {{ $communication->type?->name ?? 'Communication' }}
                                </span>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $communication->direction === 'inbound' ? 'bg-violet-100 text-violet-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ str($communication->direction)->title() }}
                                </span>
                                @if ($communication->outcome)
                                    <span class="text-sm font-semibold text-gw-primary">{{ $communication->outcome }}</span>
                                @endif
                            </div>
                            <time class="text-xs text-slate-500">{{ $communication->contacted_at?->format('M j, Y g:i A') }}</time>
                        </div>
                        @if ($communication->notes)
                            <p class="mt-2 text-sm text-slate-700">{{ $communication->notes }}</p>
                        @endif
                        @if ($communication->next_action || $communication->next_follow_up_at)
                            <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm">
                                @if ($communication->next_action)
                                    <p class="font-semibold text-gw-primary">Next: {{ $communication->next_action }}</p>
                                @endif
                                @if ($communication->next_follow_up_at)
                                    <p class="mt-1 text-xs text-slate-600">Follow-up {{ $communication->next_follow_up_at->format('M j, Y g:i A') }}</p>
                                @endif
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-slate-500">
                            Logged by {{ $communication->user?->name ?? 'Unknown' }}
                            @if ($communication->duration_minutes)
                                · {{ $communication->duration_minutes }} min
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-gw-primary">No calls or communications yet</p>
                        <p class="mt-1 text-sm text-slate-500">Track voicemails, emails, texts, and mentor-assisted calls here.</p>
                        @if ($canLogActivities)
                            <button
                                type="button"
                                wire:click="openLogCommunication"
                                class="mt-4 rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white"
                            >
                                Log first communication
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        @endif

        @if ($activeTab === 'calendar')
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-600">{{ $calendarEvents->count() }} linked calendar {{ str('event')->plural($calendarEvents->count()) }}</p>
                <a href="{{ route('team.prospects.appointments') }}" class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white">
                    Schedule Appointment
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($calendarEvents as $event)
                    <a href="{{ route('calendar.events.show', $event) }}" class="block rounded-lg border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:border-gw-accent/60">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $event->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $event->type?->name ?? 'Calendar Event' }} - {{ str($event->status)->headline() }}</p>
                            </div>
                            <time class="text-xs font-semibold text-slate-600">{{ $event->starts_at?->format('M j, Y g:i A') }}</time>
                        </div>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-gw-primary">No linked calendar events yet</p>
                        <p class="mt-1 text-sm text-slate-500">Scheduled CRM appointments will appear here automatically.</p>
                    </div>
                @endforelse
            </div>
        @endif

        @if ($activeTab === 'notes')
            @if ($canAddNotes)
                <form wire:submit="addNote" class="mb-6 space-y-3 rounded-lg border border-gw-accent/40 bg-gw-accent-soft p-4">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Add Note</span>
                        <textarea wire:model="noteBody" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                        @error('noteBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input wire:model="noteIsPrivate" type="checkbox" class="rounded border-slate-300">
                        Private note
                    </label>
                    <div>
                        <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Save Note</button>
                    </div>
                </form>
            @endif

            <div class="space-y-3">
                @forelse ($notes as $note)
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gw-primary">{{ $note->user?->name ?? 'Unknown' }}</span>
                            <span class="text-xs text-slate-500">{{ $note->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        @if ($note->is_private)
                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">Private</span>
                        @endif
                        <p class="mt-2 text-sm text-slate-700">{{ $note->note }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No notes yet.</p>
                @endforelse
            </div>
        @endif

        @if ($activeTab === 'fna')
            <livewire:prospects.prospect-fna-panel :prospect="$prospect" :key="'fna-'.$prospect->id" />
        @endif
    </div>
</div>
