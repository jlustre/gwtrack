<div
    x-show="$wire.createOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/70 px-4 py-6 sm:py-10"
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-calendar-event-title"
>
    <div x-on:click.outside="$wire.closeCreateModal()" class="w-full max-w-5xl overflow-hidden rounded-2xl border border-gw-accent/50 bg-white shadow-2xl">
        <div class="bg-gradient-to-r from-gw-primary via-[#132B4B] to-gw-primary px-6 py-5 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Create Schedule</p>
                    <h2 id="create-calendar-event-title" class="mt-1 text-2xl font-semibold">Add Calendar Event</h2>
                    <p class="mt-1 max-w-2xl text-sm text-slate-200">
                        Schedule trainings, mentor sessions, prospect appointments, licensing reviews, rank advancement meetings, or private reminders.
                    </p>
                </div>

                <button type="button" x-on:click="$wire.closeCreateModal()" class="gw-icon-btn-overlay h-10 w-10 shrink-0" aria-label="Close create event modal">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        </div>

        <form wire:submit.prevent="createEvent" class="bg-[#F5F7FA]">

            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-5 p-5 sm:p-6">
                    <section class="rounded-xl border border-[#516070] bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gw-accent-soft text-gw-accent ring-1 ring-gw-accent/40">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4.25 2A2.25 2.25 0 0 0 2 4.25v11.5A2.25 2.25 0 0 0 4.25 18h11.5A2.25 2.25 0 0 0 18 15.75V4.25A2.25 2.25 0 0 0 15.75 2H4.25ZM10 5.25a.75.75 0 0 1 .75.75v3.25H14a.75.75 0 0 1 0 1.5h-3.25V14a.75.75 0 0 1-1.5 0v-3.25H6a.75.75 0 0 1 0-1.5h3.25V6a.75.75 0 0 1 .75-.75Z" /></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-gw-primary">Event Basics</h3>
                                <p class="text-xs text-slate-500">Name the schedule and classify it for the right workflow.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="event-title" class="block text-sm font-semibold text-gw-primary">Event Title</label>
                                <input id="event-title" wire:model="eventForm.title" required class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Example: Fast Start Training, CFM Session, Prospect Overview">
                                @error('eventForm.title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-type" class="block text-sm font-semibold text-gw-primary">Event Type</label>
                                <select id="event-type" wire:model="eventForm.calendar_event_type_id" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    <option value="">General Event</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('eventForm.calendar_event_type_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-category" class="block text-sm font-semibold text-gw-primary">Calendar Category</label>
                                <select id="event-category" wire:model="eventForm.calendar_category_id" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    <option value="">Use type category</option>
                                    @foreach (($assignableCategories ?? $categories) as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('eventForm.calendar_category_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="event-description" class="block text-sm font-semibold text-gw-primary">Description / Agenda</label>
                                <textarea id="event-description" wire:model="eventForm.description" rows="4" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Add agenda, preparation notes, expected outcomes, or links to pre-work."></textarea>
                                @error('eventForm.description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-[#516070] bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EFF6FF] text-blue-700 ring-1 ring-blue-200">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2ZM3.5 8.5v6.75c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25V8.5h-13Z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-gw-primary">Date, Time, And Place</h3>
                                <p class="text-xs text-slate-500">Set the schedule details and meeting location.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="event-starts-at" class="block text-sm font-semibold text-gw-primary">Starts At</label>
                                <input id="event-starts-at" wire:model="eventForm.starts_at" type="datetime-local" required class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('eventForm.starts_at') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-ends-at" class="block text-sm font-semibold text-gw-primary">Ends At</label>
                                <input id="event-ends-at" wire:model="eventForm.ends_at" type="datetime-local" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('eventForm.ends_at') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-timezone" class="block text-sm font-semibold text-gw-primary">Timezone</label>
                                <select id="event-timezone" wire:model="eventForm.timezone" required class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    @foreach ($eventTimezones as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('eventForm.timezone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 rounded-lg border border-[#516070]/30 bg-[#F8FAFC] px-3 py-3 text-sm font-semibold text-gw-primary">
                                    <input type="checkbox" wire:model.live="eventForm.is_all_day" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                                    All Day
                                </label>
                                <label class="flex items-center gap-3 rounded-lg border border-[#516070]/30 bg-[#F8FAFC] px-3 py-3 text-sm font-semibold text-gw-primary">
                                    <input type="checkbox" wire:model.live="eventForm.is_recurring" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                                    Recurring
                                </label>
                            </div>

                            <div>
                                <label for="event-location" class="block text-sm font-semibold text-gw-primary">Location</label>
                                <input id="event-location" wire:model="eventForm.location" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Office, hotel, training room, or virtual">
                                @error('eventForm.location') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-meeting-link" class="block text-sm font-semibold text-gw-primary">Meeting Link</label>
                                <input id="event-meeting-link" wire:model="eventForm.meeting_link" type="url" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="https://zoom.us/j/...">
                                @error('eventForm.meeting_link') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div x-show="$wire.eventForm.is_recurring" x-transition class="md:col-span-2">
                                <div class="rounded-xl border border-gw-accent/50 bg-gw-accent-soft p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-semibold text-gw-primary">Repeat Schedule</h4>
                                            <p class="mt-1 text-xs text-slate-600">Choose how often this event repeats. The system will build the calendar rule for you.</p>
                                        </div>
                                        <span class="hidden rounded-full bg-white px-3 py-1 text-xs font-bold text-gw-accent ring-1 ring-gw-accent/40 sm:inline-flex">No typing needed</span>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label for="recurrence-frequency" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Repeats</label>
                                            <select id="recurrence-frequency" wire:model.live="eventForm.recurrence_frequency" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                            </select>
                                            @error('eventForm.recurrence_frequency') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label for="recurrence-interval" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Every</label>
                                            <div class="mt-2 flex items-center gap-2">
                                                <input id="recurrence-interval" wire:model="eventForm.recurrence_interval" type="number" min="1" max="12" class="block w-24 rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                                <span class="text-sm font-semibold text-gw-primary" x-text="{ daily: 'day(s)', weekly: 'week(s)', monthly: 'month(s)', yearly: 'year(s)' }[$wire.eventForm.recurrence_frequency]"></span>
                                            </div>
                                            @error('eventForm.recurrence_interval') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div x-show="$wire.eventForm.recurrence_frequency === 'weekly'" x-transition class="mt-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Repeat On</p>
                                        <div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-7">
                                            @foreach (['MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun'] as $code => $label)
                                                <label class="flex items-center justify-center gap-2 rounded-lg border border-[#516070]/30 bg-white px-3 py-2 text-sm font-semibold text-gw-primary transition hover:border-gw-accent hover:bg-gw-accent-muted">
                                                    <input type="checkbox" wire:model="eventForm.recurrence_weekdays" value="{{ $code }}" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500">If no day is selected, it will repeat on the start date's weekday.</p>
                                        @error('eventForm.recurrence_weekdays') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="mt-4">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Ends</p>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                            <label class="flex items-center gap-3 rounded-lg border border-[#516070]/30 bg-white px-3 py-2 text-sm font-semibold text-gw-primary">
                                                <input type="radio" wire:model.live="eventForm.recurrence_end_type" value="never" class="border-[#516070] text-gw-accent focus:ring-gw-accent">
                                                Never
                                            </label>
                                            <label class="flex items-center gap-3 rounded-lg border border-[#516070]/30 bg-white px-3 py-2 text-sm font-semibold text-gw-primary">
                                                <input type="radio" wire:model.live="eventForm.recurrence_end_type" value="after" class="border-[#516070] text-gw-accent focus:ring-gw-accent">
                                                After
                                            </label>
                                            <label class="flex items-center gap-3 rounded-lg border border-[#516070]/30 bg-white px-3 py-2 text-sm font-semibold text-gw-primary">
                                                <input type="radio" wire:model.live="eventForm.recurrence_end_type" value="on" class="border-[#516070] text-gw-accent focus:ring-gw-accent">
                                                On date
                                            </label>
                                        </div>

                                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                                            <div x-show="$wire.eventForm.recurrence_end_type === 'after'" x-transition>
                                                <label for="recurrence-occurrences" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Occurrences</label>
                                                <input id="recurrence-occurrences" wire:model="eventForm.recurrence_ends_after_occurrences" type="number" min="1" max="120" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                                @error('eventForm.recurrence_ends_after_occurrences') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>

                                            <div x-show="$wire.eventForm.recurrence_end_type === 'on'" x-transition>
                                                <label for="recurrence-ends-on" class="block text-xs font-bold uppercase tracking-wide text-slate-500">End Date</label>
                                                <input id="recurrence-ends-on" wire:model="eventForm.recurrence_ends_on" type="date" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                                @error('eventForm.recurrence_ends_on') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-[#516070] bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4 16.25A4.25 4.25 0 0 1 8.25 12h3.5A4.25 4.25 0 0 1 16 16.25a.75.75 0 0 1-.75.75H4.75A.75.75 0 0 1 4 16.25Z" /></svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold text-gw-primary">Attendees</h3>
                                <p class="text-xs text-slate-500">Invite GWTrack users or add one outside guest/prospect.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="event-attendees" class="block text-sm font-semibold text-gw-primary">GWTrack Attendees</label>
                                <select id="event-attendees" wire:model="eventForm.attendee_user_ids" multiple size="6" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    @foreach ($attendeeUsers as $attendee)
                                        <option value="{{ $attendee->id }}">{{ $attendee->name }} - {{ $attendee->email }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-slate-500">Hold Ctrl or Command to select multiple attendees.</p>
                                @error('eventForm.attendee_user_ids') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-external-name" class="block text-sm font-semibold text-gw-primary">Outside Guest Name</label>
                                <input id="event-external-name" wire:model="eventForm.external_attendee_name" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Prospect or guest name">
                                @error('eventForm.external_attendee_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="event-external-email" class="block text-sm font-semibold text-gw-primary">Outside Guest Email</label>
                                <input id="event-external-email" wire:model="eventForm.external_attendee_email" type="email" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="guest@example.com">
                                @error('eventForm.external_attendee_email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <div class="border-t border-[#516070]/20 bg-white p-5 lg:border-l lg:border-t-0">
                    <div class="space-y-5">
                        <section class="rounded-xl border border-[#516070] bg-[#F8FAFC] p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-gw-primary">Access And Status</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="event-visibility" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Visibility</label>
                                    <select id="event-visibility" wire:model="eventForm.visibility" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                        <option value="private">Private</option>
                                        <option value="shared_team">Shared Team</option>
                                        <option value="shared_downline">Shared Downline</option>
                                        <option value="public_organization">Public Organization</option>
                                    </select>
                                    @error('eventForm.visibility') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="event-status" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                                    <select id="event-status" wire:model="eventForm.status" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                        <option value="scheduled">Scheduled</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                    @error('eventForm.status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-[#516070] bg-gw-accent-soft p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-gw-primary">Reminders</h3>
                            <div class="mt-4 space-y-3">
                                @foreach ([15 => '15 minutes before', 30 => '30 minutes before', 60 => '1 hour before', 1440 => '1 day before'] as $minutes => $label)
                                    <label class="flex items-center gap-3 rounded-lg border border-gw-accent/40 bg-white px-3 py-2 text-sm font-medium text-gw-primary">
                                        <input type="checkbox" wire:model="eventForm.reminder_minutes" value="{{ $minutes }}" class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                                        {{ $label }}
                                    </label>
                                @endforeach
                                @error('eventForm.reminder_minutes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                                <div>
                                    <label for="event-reminder-channel" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Reminder Channel</label>
                                    <select id="event-reminder-channel" wire:model="eventForm.reminder_channel" class="mt-2 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                        <option value="in_app">In-app</option>
                                        <option value="email">Email</option>
                                        <option value="both">In-app and Email</option>
                                    </select>
                                    @error('eventForm.reminder_channel') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-[#516070] bg-white p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-gw-primary">Organizer Notes</h3>
                            <textarea wire:model="eventForm.notes" rows="5" class="mt-3 block w-full rounded-lg border-[#516070]/40 bg-white text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Internal follow-up, preparation, or post-event notes."></textarea>
                            @error('eventForm.notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </section>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end lg:flex-col-reverse">
                            <button type="button" x-on:click="$wire.closeCreateModal()" class="inline-flex justify-center rounded-lg border border-[#516070]/40 bg-white px-4 py-3 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex justify-center rounded-lg bg-gw-accent px-4 py-3 text-sm font-bold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">
                                Save Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
