<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="dashboard-log-activity-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div @class([
            'relative z-10 flex w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl',
            'h-[92vh]' => $step !== 'search' || $showLogForm || $showScheduleForm,
            'max-h-[88vh]' => $step === 'search' && ! $showLogForm && ! $showScheduleForm,
        ])>
            <div class="border-b border-[#7E22CE]/40 bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#0F766E] px-5 py-3 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Prospect Activity</p>
                        @if ($step === 'search')
                            <h3 id="dashboard-log-activity-modal-title" class="mt-0.5 text-base font-semibold">Choose a person</h3>
                            <p class="mt-0.5 text-xs text-violet-100/90">Search prospects, clients, or team members to log activity or schedule a future touchpoint.</p>
                        @elseif ($step === 'unresolved')
                            <h3 id="dashboard-log-activity-modal-title" class="mt-0.5 text-base font-semibold">{{ $selectedPersonLabel }}</h3>
                            <p class="mt-0.5 text-xs text-violet-100/90">{{ $selectedPersonSubtitle }} · no matching CRM record</p>
                        @else
                            <h3 id="dashboard-log-activity-modal-title" class="mt-0.5 text-base font-semibold">{{ $selectedPersonLabel }}</h3>
                            <p class="mt-0.5 text-xs text-violet-100/90">{{ $selectedPersonSubtitle }} · log past touchpoints or plan upcoming activity.</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($step !== 'search')
                            <button
                                type="button"
                                wire:click="backToSearch"
                                class="rounded-md border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25"
                            >
                                Change person
                            </button>
                        @endif
                        @if ($step === 'detail' && $prospect)
                            @if ($viewTab === 'log')
                                <button
                                    type="button"
                                    wire:click="openAddActivityForm"
                                    class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary transition hover:bg-gw-accent-hover"
                                >
                                    {{ $showLogForm ? 'Adding activity…' : 'Log Activity' }}
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="openScheduleForm"
                                    class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary transition hover:bg-gw-accent-hover"
                                >
                                    {{ $showScheduleForm ? 'Scheduling…' : 'Schedule Activity' }}
                                </button>
                            @endif
                        @endif
                        <a
                            href="{{ $activityLogsUrl }}"
                            wire:click="close"
                            class="inline-flex items-center gap-1 rounded-md border border-gw-accent/60 bg-gw-accent/20 px-3 py-1.5 text-xs font-bold text-gw-accent-soft transition hover:bg-gw-accent/30"
                        >
                            Activity Logs
                        </a>
                        <a href="{{ $appointmentsUrl }}" wire:click="close" class="inline-flex items-center gap-1 rounded-md border border-white/25 bg-white/10 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/20">
                            Appointments
                        </a>
                        <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                    </div>
                </div>
            </div>

            @if ($statusMessage)
                <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-2 text-xs font-semibold text-emerald-800">
                    {{ $statusMessage }}
                </div>
            @endif

            @if ($step === 'search')
                <div class="border-b border-slate-200 px-4 py-3">
                    <label for="dashboard-log-activity-search" class="sr-only">Search people</label>
                    <input
                        id="dashboard-log-activity-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search prospect, client, or member (min. 3 characters)..."
                        class="h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                    @if (strlen(trim($search)) > 0 && strlen(trim($search)) < 3)
                        <p class="mt-1 text-xs text-slate-500">Type at least 3 characters to search.</p>
                    @endif
                    @error('search') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    @if (strlen(trim($search)) >= 3)
                        @forelse ($personOptions as $person)
                            <button
                                type="button"
                                wire:key="dashboard-activity-person-{{ $person->key }}"
                                wire:click="selectPerson(@js($person->key), @js($person->label), @js($person->subtitle))"
                                class="mb-2 flex w-full items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-left transition hover:border-gw-accent/50 hover:bg-gw-accent-soft"
                            >
                                <span>
                                    <span class="block text-sm font-semibold text-gw-primary">{{ $person->label }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $person->subtitle }}@if ($person->meta) · {{ $person->meta }}@endif</span>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wide text-[#7E22CE]">Select</span>
                            </button>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-gw-primary">No matching people found</p>
                                <p class="mt-1 text-xs text-slate-500">Try a different search term.</p>
                            </div>
                        @endforelse
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-gw-primary">Search to get started</p>
                            <p class="mt-1 text-xs text-slate-500">Find a prospect or client to log activity or schedule a future touchpoint.</p>
                        </div>
                    @endif
                </div>
            @elseif ($step === 'unresolved')
                <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-gw-primary">No CRM record found for this team member</p>
                    <p class="mt-2 max-w-md text-sm text-slate-600">
                        Activity logging and scheduling are tied to prospect and client records. Add this person as a prospect first, or search for an existing CRM record by name or email.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <button type="button" wire:click="backToSearch" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Search again
                        </button>
                        <a href="{{ $activityLogsUrl }}" wire:click="close" class="rounded-md bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#0F766E] px-4 py-2 text-sm font-semibold text-white hover:brightness-110">
                            Open Activity Logs
                        </a>
                    </div>
                </div>
            @else
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
                    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                        <button
                            type="button"
                            wire:click="switchTab('log')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                'bg-[#5B21B6] text-white shadow-sm' => $viewTab === 'log',
                                'text-slate-600 hover:bg-slate-100' => $viewTab !== 'log',
                            ])
                        >
                            Log Activity
                        </button>
                        <button
                            type="button"
                            wire:click="switchTab('schedule')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                'bg-[#0F766E] text-white shadow-sm' => $viewTab === 'schedule',
                                'text-slate-600 hover:bg-slate-100' => $viewTab !== 'schedule',
                            ])
                        >
                            Schedule Activity
                        </button>
                    </div>
                </div>

                @if ($viewTab === 'log')
                    @if ($showLogForm && $prospectId)
                        <div class="border-b border-slate-200 p-4">
                            <livewire:prospects.prospect-activity-log-form
                                :prospect-id="$prospectId"
                                :activity-id="$editingActivityId"
                                :key="'dashboard-activity-form-'.$prospectId.'-'.$activityFormKey.'-'.($editingActivityId ?? 'new')"
                            />
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-2.5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Recent activity</p>
                            <p class="text-sm font-semibold text-gw-primary">{{ $activities->count() }} logged touchpoint{{ $activities->count() === 1 ? '' : 's' }}</p>
                        </div>
                        @if (! $showLogForm)
                            <button
                                type="button"
                                wire:click="openAddActivityForm"
                                class="inline-flex items-center gap-1 rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                            >
                                Log Activity
                            </button>
                        @endif
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">When</th>
                                    <th class="px-4 py-2.5">Activity</th>
                                    <th class="hidden px-4 py-2.5 sm:table-cell">Subject</th>
                                    <th class="px-4 py-2.5">Outcome</th>
                                    <th class="hidden px-4 py-2.5 md:table-cell">Next action</th>
                                <th class="hidden px-4 py-2.5 lg:table-cell">Logged by</th>
                                <th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($activities as $activity)
                                <tr wire:key="dashboard-activity-{{ $activity->id }}" @class(['bg-gw-accent-soft/60' => $viewingActivityId === $activity->id || $editingActivityId === $activity->id])>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $activity->occurred_at?->format('M j, g:i A') ?? '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-gw-primary">{{ \App\Support\ProspectActivityForm::activityTypeLabel($activity->activity_type) }}</p>
                                            @if ($activity->duration_minutes)
                                                <p class="text-[11px] text-slate-500">{{ $activity->duration_minutes }} min</p>
                                            @endif
                                        </td>
                                        <td class="hidden px-4 py-2.5 text-slate-600 sm:table-cell">{{ $activity->metadata['subject'] ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $activity->outcome ?? 'Not recorded' }}</td>
                                        <td class="hidden px-4 py-2.5 text-slate-600 md:table-cell">{{ $activity->next_action ?? '—' }}</td>
                                    <td class="hidden px-4 py-2.5 text-slate-500 lg:table-cell">{{ $activity->user?->name ?? 'System' }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <button type="button" wire:click="viewActivity({{ $activity->id }})" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">View</button>
                                            <button type="button" wire:click="editActivity({{ $activity->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[11px] font-semibold text-gw-primary hover:bg-gw-accent-muted">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                                @if ($viewingActivityId === $activity->id)
                                    <tr wire:key="dashboard-activity-view-{{ $activity->id }}" class="bg-slate-50">
                                        <td colspan="7" class="px-4 py-4">
                                            <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Activity details</p>
                                                        <p class="mt-1 font-semibold text-gw-primary">{{ \App\Support\ProspectActivityForm::activityTypeLabel($activity->activity_type) }}</p>
                                                    </div>
                                                    <div class="flex gap-1.5">
                                                        <button type="button" wire:click="editActivity({{ $activity->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[11px] font-semibold text-gw-primary">Edit</button>
                                                        <button type="button" wire:click="closeActivityPanel" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">Close</button>
                                                    </div>
                                                </div>
                                                <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                                                    <div><dt class="text-[11px] font-bold uppercase text-slate-500">When</dt><dd class="text-slate-700">{{ $activity->occurred_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
                                                    <div><dt class="text-[11px] font-bold uppercase text-slate-500">Outcome</dt><dd class="text-slate-700">{{ $activity->outcome ?? 'Not recorded' }}</dd></div>
                                                    <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Subject</dt><dd class="text-slate-700">{{ $activity->metadata['subject'] ?? '—' }}</dd></div>
                                                    <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Notes</dt><dd class="text-slate-700">{{ $activity->notes ?? '—' }}</dd></div>
                                                    <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Next action</dt><dd class="text-slate-700">{{ $activity->next_action ?? '—' }}</dd></div>
                                                    @if ($activity->next_follow_up_at)
                                                        <div><dt class="text-[11px] font-bold uppercase text-slate-500">Follow up</dt><dd class="text-slate-700">{{ $activity->next_follow_up_at->format('M j, Y g:i A') }}</dd></div>
                                                    @endif
                                                </dl>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center">
                                            <p class="text-sm font-semibold text-gw-primary">No activities logged yet</p>
                                            <p class="mt-1 text-xs text-slate-500">Click <span class="font-semibold">Log Activity</span> to record the first touchpoint.</p>
                                            <button type="button" wire:click="openAddActivityForm" class="mt-4 inline-flex items-center gap-1 rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                                                Log Activity
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($prospect)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                            <p class="text-xs text-slate-500">Open the full activity timeline for notes, communications, and status updates.</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="openFullLogTools" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Full log tools
                                </button>
                                <a href="{{ route('team.prospects.records.activity', $prospect) }}" wire:click="close" class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#7E22CE] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110">
                                    View all activity
                                </a>
                            </div>
                        </div>
                    @endif
                @else
                    @if ($showScheduleForm && $prospectId)
                        <div class="border-b border-slate-200 p-4">
                            <livewire:prospects.prospect-schedule-activity-form
                                :prospect-id="$prospectId"
                                :appointment-id="$editingAppointmentId"
                                :key="'dashboard-schedule-form-'.$prospectId.'-'.$scheduleFormKey.'-'.($editingAppointmentId ?? 'new')"
                            />
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-2.5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Upcoming schedule</p>
                            <p class="text-sm font-semibold text-gw-primary">{{ $upcomingAppointments->count() }} planned touchpoint{{ $upcomingAppointments->count() === 1 ? '' : 's' }}</p>
                        </div>
                        @if (! $showScheduleForm)
                            <button type="button" wire:click="openScheduleForm" class="inline-flex items-center gap-1 rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                                Schedule Activity
                            </button>
                        @endif
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">When</th>
                                    <th class="px-4 py-2.5">Type</th>
                                    <th class="hidden px-4 py-2.5 sm:table-cell">Purpose</th>
                                    <th class="hidden px-4 py-2.5 md:table-cell">Location</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($upcomingAppointments as $appointment)
                                    <tr wire:key="dashboard-schedule-{{ $appointment->id }}" @class(['bg-gw-accent-soft/60' => $viewingAppointmentId === $appointment->id || $editingAppointmentId === $appointment->id])>
                                        <td class="px-4 py-2.5 text-slate-600">{{ $appointment->scheduled_at?->format('M j, g:i A') ?? '—' }}</td>
                                        <td class="px-4 py-2.5 font-medium text-gw-primary">{{ $appointment->type?->name ?? 'Appointment' }}</td>
                                        <td class="hidden px-4 py-2.5 text-slate-600 sm:table-cell">{{ $appointment->purpose ?? '—' }}</td>
                                        <td class="hidden px-4 py-2.5 text-slate-600 md:table-cell">{{ $appointment->location_or_link ?? '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">{{ str($appointment->status)->title() }}</span>
                                            @if ($appointment->calendar_event_id)
                                                <span class="mt-1 block text-[10px] text-slate-500">On calendar</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <button type="button" wire:click="viewAppointment({{ $appointment->id }})" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">View</button>
                                                <button type="button" wire:click="editAppointment({{ $appointment->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[11px] font-semibold text-gw-primary hover:bg-gw-accent-muted">Edit</button>
                                            </div>
                                        </td>
                                    </tr>
                                    @if ($viewingAppointmentId === $appointment->id)
                                        <tr wire:key="dashboard-schedule-view-{{ $appointment->id }}" class="bg-slate-50">
                                            <td colspan="6" class="px-4 py-4">
                                                <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Scheduled activity</p>
                                                            <p class="mt-1 font-semibold text-gw-primary">{{ $appointment->type?->name ?? 'Appointment' }}</p>
                                                        </div>
                                                        <div class="flex gap-1.5">
                                                            <button type="button" wire:click="editAppointment({{ $appointment->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2.5 py-1 text-[11px] font-semibold text-gw-primary">Edit</button>
                                                            <button type="button" wire:click="closeAppointmentPanel" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700">Close</button>
                                                        </div>
                                                    </div>
                                                    <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                                                        <div><dt class="text-[11px] font-bold uppercase text-slate-500">When</dt><dd class="text-slate-700">{{ $appointment->scheduled_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
                                                        <div><dt class="text-[11px] font-bold uppercase text-slate-500">Status</dt><dd class="text-slate-700">{{ str($appointment->status)->title() }}</dd></div>
                                                        <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Purpose</dt><dd class="text-slate-700">{{ $appointment->purpose ?? '—' }}</dd></div>
                                                        <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Location</dt><dd class="text-slate-700">{{ $appointment->location_or_link ?? '—' }}</dd></div>
                                                        <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Notes</dt><dd class="text-slate-700">{{ $appointment->notes ?? '—' }}</dd></div>
                                                        @if ($appointment->calendar_event_id)
                                                            <div class="sm:col-span-2"><dt class="text-[11px] font-bold uppercase text-slate-500">Calendar</dt><dd class="text-emerald-700">Synced to calendar</dd></div>
                                                        @endif
                                                    </dl>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center">
                                            <p class="text-sm font-semibold text-gw-primary">Nothing scheduled yet</p>
                                            <p class="mt-1 text-xs text-slate-500">Click <span class="font-semibold">Schedule Activity</span> to plan the next touchpoint.</p>
                                            <button type="button" wire:click="openScheduleForm" class="mt-4 inline-flex items-center gap-1 rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                                                Schedule Activity
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($prospect)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                            <p class="text-xs text-slate-500">Scheduled activities sync to Calendar, Tasks, and the prospect appointment hub.</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('team.prospects.records.show', $prospect) }}" wire:click="close" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Prospect profile
                                </a>
                                <a href="{{ $appointmentsUrl }}" wire:click="close" class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0F766E] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110">
                                    Appointment hub
                                </a>
                                <a href="{{ $calendarUrl }}" wire:click="close" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Calendar
                                </a>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            @if ($step === 'search')
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <p class="text-xs text-slate-500">Review activity history or upcoming appointments from the CRM hubs.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $activityLogsUrl }}" wire:click="close" class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#2E1065] via-[#5B21B6] to-[#7E22CE] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110">
                            Activity Logs
                        </a>
                        <a href="{{ $appointmentsUrl }}" wire:click="close" class="inline-flex items-center gap-1 rounded-md bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0F766E] px-3 py-2 text-xs font-semibold text-white transition hover:brightness-110">
                            Appointments
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
