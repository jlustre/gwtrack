@if ($viewingEvent)
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/60 px-4 py-6 sm:py-10"
        role="dialog"
        aria-modal="true"
        aria-labelledby="calendar-event-detail-title"
        wire:key="event-detail-{{ $viewingEvent->id }}"
    >
        <div
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-gw-accent/50 bg-white shadow-2xl"
            x-on:click.outside="$wire.closeEventDetail()"
        >
            <div class="bg-gradient-to-r from-gw-primary via-[#132B4B] to-gw-primary px-5 py-4 text-white sm:px-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Calendar Event</p>
                        <h2 id="calendar-event-detail-title" class="mt-1 text-xl font-semibold sm:text-2xl">{{ $viewingEvent->title }}</h2>
                        <p class="mt-1 text-sm text-slate-200">
                            {{ $viewingEvent->starts_at?->format('F j, Y g:i A') }}{{ $viewingEvent->ends_at ? ' – '.$viewingEvent->ends_at->format('g:i A') : '' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeEventDetail" class="gw-icon-btn-overlay h-10 w-10 shrink-0" aria-label="Close event details">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid gap-4 bg-[#F5F7FA] p-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div class="space-y-4 rounded-xl border border-[#516070]/20 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold text-gw-primary" style="background-color: {{ $viewingEvent->display_color }}33; border: 1px solid {{ $viewingEvent->display_color }}">
                            {{ $viewingEvent->type?->name ?? 'Event' }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($viewingEvent->status)->headline() }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($viewingEvent->visibility)->headline() }}</span>
                        @if ($viewingEvent->category)
                            <span class="rounded-full bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-accent-dark">{{ $viewingEvent->category->name }}</span>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gw-primary">Description</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $viewingEvent->description ?: 'No description has been added yet.' }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</p>
                            <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $viewingEvent->location ?: 'Virtual or not set' }}</p>
                        </div>
                        <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Meeting Link</p>
                            @if ($viewingEvent->meeting_link)
                                <a href="{{ $viewingEvent->meeting_link }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-sm font-semibold text-gw-primary underline">
                                    {{ $viewingEvent->meeting_link }}
                                </a>
                            @else
                                <p class="mt-1 text-sm font-semibold text-gw-primary">Not set</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-[#516070]/20 bg-white p-4 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Organizer</p>
                        <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $viewingEvent->organizer?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $viewingEvent->organizer?->email }}</p>
                    </div>

                    @if ($viewingEvent->relatedProspect)
                        <div class="rounded-xl border border-[#516070]/20 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Linked Prospect</p>
                            <a href="{{ route('team.prospects.records.show', $viewingEvent->relatedProspect) }}" class="mt-2 block text-sm font-semibold text-gw-primary hover:text-gw-accent-dark">
                                {{ $viewingEvent->relatedProspect->displayName() }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ str($viewingEvent->relatedProspect->funnel_type ?? 'insurance')->headline() }}{{ $viewingEvent->relatedProspect->stage ? ' - '.$viewingEvent->relatedProspect->stage->name : '' }}
                            </p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-[#516070]/20 bg-white p-4 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Attendees</p>
                        <div class="mt-3 max-h-48 space-y-2 overflow-y-auto">
                            @forelse ($viewingEvent->attendees as $attendee)
                                <div class="rounded-md bg-[#F8FAFC] px-3 py-2">
                                    <div class="text-sm font-semibold text-gw-primary">{{ $attendee->user?->name ?? $attendee->name ?? $attendee->email }}</div>
                                    <div class="text-xs text-slate-500">{{ str($attendee->rsvp_status)->headline() }}</div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No attendees yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 bg-white px-5 py-4">
                <a
                    href="{{ route('calendar.events.show', $viewingEvent) }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Open full page
                </a>
                <button type="button" wire:click="closeEventDetail" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">
                    Close
                </button>
            </div>
        </div>
    </div>
@endif
