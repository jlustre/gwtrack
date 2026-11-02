@php
    $today = $panels['today'] ?? collect();
    $tomorrow = $panels['tomorrow'] ?? collect();
    $later = $panels['later'] ?? collect();
    $followUp = $panels['follow_up'] ?? collect();
    $stats = $panels['stats'] ?? ['total' => 0, 'today' => 0, 'tomorrow' => 0, 'follow_up' => 0, 'active' => 0];
    $hasCalls = ($stats['total'] ?? 0) > 0;
@endphp

<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="dashboard-schedule-phone-call-modal-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

        <div @class([
            'relative z-10 flex w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-xl',
            'h-[92vh]' => ! in_array($step, ['search'], true) || $showScheduleForm,
            'max-h-[88vh]' => $step === 'search' && ! $showScheduleForm,
        ])>
            <div class="border-b border-[#1D4ED8]/40 bg-gradient-to-br from-[#1E3A8A] via-[#1D4ED8] to-[#2563EB] px-5 py-3 text-white">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Phone Call Reminder</p>
                        @if ($step === 'list')
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">Upcoming phone calls</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">View, reschedule, edit, or delete scheduled calls — or plan a new one.</p>
                        @elseif ($step === 'search')
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">Schedule a phone call</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">Search prospects, clients, team members, or add someone outside the system.</p>
                        @elseif ($step === 'external')
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">Call someone not in GWTracks</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">Enter their details and when you plan to call.</p>
                        @elseif ($step === 'view' && $activeCall)
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">{{ $activeCall->contact_name }}</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">{{ $activeCall->personTypeLabel() }} · {{ $activeCall->scheduled_at->format('M j, g:i A') }}</p>
                        @elseif ($step === 'edit')
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">Edit phone call</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">Update contact details, timing, or notes.</p>
                        @elseif ($step === 'reschedule' && $activeCall)
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">Reschedule call with {{ $activeCall->contact_name }}</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">Pick a new date and time for this call.</p>
                        @else
                            <h3 id="dashboard-schedule-phone-call-modal-title" class="mt-0.5 text-base font-semibold">{{ $selectedPersonLabel }}</h3>
                            <p class="mt-0.5 text-xs text-blue-100/90">{{ $selectedPersonSubtitle }} · phone call schedule</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($step === 'list')
                            <button type="button" wire:click="startScheduleNew" class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary transition hover:bg-gw-accent-hover">
                                Schedule new call
                            </button>
                        @else
                            <button type="button" wire:click="backToList" class="rounded-md border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                                Back to list
                            </button>
                            @if (in_array($step, ['detail', 'external'], true))
                                <button type="button" wire:click="backToSearch" class="rounded-md border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                                    Change person
                                </button>
                            @endif
                        @endif
                        @if ($step === 'detail' && ! $showScheduleForm)
                            <button type="button" wire:click="openScheduleForm" class="rounded-md bg-gw-accent px-3 py-1.5 text-xs font-bold text-gw-primary transition hover:bg-gw-accent-hover">
                                Schedule call
                            </button>
                        @endif
                        <a href="{{ $calendarUrl }}" wire:navigate x-on:click="$wire.close()" class="inline-flex items-center gap-1 rounded-md border border-white/25 bg-white/10 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/20">
                            Calendar
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

            @if ($step === 'list')
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    @if (! $hasCalls)
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-gw-primary">No upcoming phone calls</p>
                            <p class="mt-1 text-xs text-slate-500">Schedule your first call to build your calling list.</p>
                            <button type="button" wire:click="startScheduleNew" class="mt-4 rounded-md bg-gradient-to-br from-[#1E3A8A] via-[#1D4ED8] to-[#2563EB] px-4 py-2 text-sm font-semibold text-white hover:brightness-110">
                                Schedule phone call
                            </button>
                        </div>
                    @else
                        <div class="space-y-4">
                            @include('livewire.dashboard.partials.phone-call-list-groups', ['panels' => $panels, 'mode' => 'modal'])
                        </div>
                    @endif
                </div>
            @elseif ($step === 'search')
                <div class="border-b border-slate-200 px-4 py-3">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search prospect, client, or team member (min. 3 characters)..." class="h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <div class="mt-3">
                        <button type="button" wire:click="openExternalForm" class="rounded-md border border-dashed border-[#1D4ED8]/40 bg-blue-50 px-3 py-2 text-xs font-semibold text-[#1D4ED8] hover:bg-blue-100">
                            Other person not in the system
                        </button>
                    </div>
                    @error('search') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    @if (strlen(trim($search)) >= 3)
                        @forelse ($personOptions as $person)
                            <button type="button" wire:key="dashboard-phone-person-{{ str_replace(':', '-', $person->key) }}" wire:click="selectPerson(@js($person->key))" class="mb-2 flex w-full items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-left transition hover:border-gw-accent/50 hover:bg-gw-accent-soft">
                                <span>
                                    <span class="block text-sm font-semibold text-gw-primary">{{ $person->label }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $person->subtitle }}@if ($person->meta) · {{ $person->meta }}@endif</span>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wide text-[#1D4ED8]">Select</span>
                            </button>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
                                No matching people found. Try <button type="button" wire:click="openExternalForm" class="font-semibold text-[#1D4ED8] hover:underline">other person not in the system</button>.
                            </div>
                        @endforelse
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-gw-primary">Search or add an external contact</p>
                            <p class="mt-1 text-xs text-slate-500">Scheduled calls appear in Upcoming Phone Calls on your dashboard and calendar.</p>
                        </div>
                    @endif
                </div>
            @elseif ($step === 'view' && $activeCall)
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-4">
                        <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Contact</dt>
                                <dd class="mt-1 font-semibold text-gw-primary">{{ $activeCall->contact_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Type</dt>
                                <dd class="mt-1 text-slate-700">{{ $activeCall->personTypeLabel() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Scheduled</dt>
                                <dd class="mt-1 text-slate-700">
                                    {{ $activeCall->scheduled_at->format('l, M j, Y g:i A') }}
                                    @if ($activeCall->isOverdue())
                                        <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800">Overdue · Follow up</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($activeCall->contact_phone)
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Phone</dt>
                                    <dd class="mt-1">
                                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $activeCall->contact_phone) }}" class="font-semibold text-[#1D4ED8] hover:underline">{{ $activeCall->contact_phone }}</a>
                                    </dd>
                                </div>
                            @endif
                            @if ($activeCall->contact_email)
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Email</dt>
                                    <dd class="mt-1 text-slate-700">{{ $activeCall->contact_email }}</dd>
                                </div>
                            @endif
                            @if ($activeCall->purpose)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Purpose</dt>
                                    <dd class="mt-1 text-slate-700">{{ $activeCall->purpose }}</dd>
                                </div>
                            @endif
                            @if ($activeCall->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Notes</dt>
                                    <dd class="mt-1 whitespace-pre-wrap text-slate-700">{{ $activeCall->notes }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                            <button type="button" wire:click="startReschedule({{ $activeCall->id }})" class="rounded-md border border-[#1D4ED8]/40 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#1D4ED8] hover:bg-blue-100">Reschedule</button>
                            <button type="button" wire:click="editCall({{ $activeCall->id }})" class="rounded-md border border-gw-accent/50 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-muted">Edit</button>
                            <button type="button" wire:click="deleteCall({{ $activeCall->id }})" wire:confirm="Delete this scheduled phone call?" class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</button>
                            @if ($activeCall->contact_phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $activeCall->contact_phone) }}" class="rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover">Call now</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Results</p>
                        @if ($activeCall->prospect_id && in_array($activeCall->person_type, ['prospect', 'client'], true))
                            <p class="mt-1 text-xs text-slate-500">Results are logged to the CRM pipeline. Only <strong>Completed</strong> removes this call from your list.</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Only <strong>Completed</strong> removes this call from your list. Use <strong>Reschedule</strong> or a callback result to move overdue calls back to the active list.</p>
                        @endif

                        <form wire:submit="saveCallResult" class="mt-4 space-y-4">
                            <div>
                                <label for="modal-call-result" class="text-sm font-semibold text-gw-primary">Result</label>
                                <select id="modal-call-result" wire:model.live="result" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    <option value="">Select result</option>
                                    @foreach ($results as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('result') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @if ($result === 'callback')
                                <div>
                                    <label for="modal-callback-at" class="text-sm font-semibold text-gw-primary">Callback date and time</label>
                                    <input id="modal-callback-at" type="datetime-local" wire:model="callbackAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    @error('callbackAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div>
                                <label for="modal-result-notes" class="text-sm font-semibold text-gw-primary">Notes</label>
                                <textarea id="modal-result-notes" wire:model="resultNotes" rows="3" placeholder="What happened on the call?" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                                @error('resultNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                                <button type="button" wire:click="backToList" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="saveCallResult" class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:opacity-60">
                                    <span wire:loading.remove wire:target="saveCallResult">Save result</span>
                                    <span wire:loading wire:target="saveCallResult">Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    @if ($followUp->isNotEmpty())
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50/50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Follow up list</p>
                            <p class="mt-1 text-xs text-slate-600">Other past-due calls that still need attention.</p>
                            <div class="mt-3 space-y-2">
                                @include('livewire.dashboard.partials.phone-call-list-groups', [
                                    'panels' => ['today' => collect(), 'tomorrow' => collect(), 'later' => collect(), 'follow_up' => $followUp],
                                    'mode' => 'modal',
                                    'excludeCallId' => $activeCall->id,
                                ])
                            </div>
                        </div>
                    @endif
                </div>
            @elseif ($step === 'reschedule')
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <form wire:submit="saveReschedule" class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-4">
                        <div>
                            <label for="modal-reschedule-at" class="text-sm font-semibold text-gw-primary">New date and time</label>
                            <input id="modal-reschedule-at" type="datetime-local" wire:model="rescheduleAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @error('rescheduleAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="modal-reschedule-notes" class="text-sm font-semibold text-gw-primary">Notes (optional)</label>
                            <textarea id="modal-reschedule-notes" wire:model="rescheduleNotes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="backToList" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="saveReschedule" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                                <span wire:loading.remove wire:target="saveReschedule">Save new time</span>
                                <span wire:loading wire:target="saveReschedule">Saving…</span>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif ($showScheduleForm)
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <livewire:dashboard.schedule-phone-call-form
                        :person="$selectedPerson"
                        :call-id="$editingCallId"
                        :key="'dashboard-phone-call-form-'.$scheduleFormKey"
                    />
                </div>
            @else
                <div class="min-h-0 flex-1 overflow-y-auto p-4 space-y-4">
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                        <p class="text-sm font-semibold text-gw-primary">Ready to schedule?</p>
                        <p class="mt-1 text-xs text-slate-500">Plan when you will call {{ $selectedPersonLabel }}.</p>
                        <button type="button" wire:click="openScheduleForm" class="mt-4 rounded-md bg-gradient-to-br from-[#1E3A8A] via-[#1D4ED8] to-[#2563EB] px-4 py-2 text-sm font-semibold text-white hover:brightness-110">
                            Schedule phone call
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
