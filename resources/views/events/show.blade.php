<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Calendar Event</p>
                <h1 class="text-2xl font-semibold text-gw-primary">{{ $event->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $event->starts_at?->format('F j, Y g:i A') }}{{ $event->ends_at ? ' - '.$event->ends_at->format('g:i A') : '' }}</p>
            </div>

            <a href="{{ route('calendar.index') }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted">
                Back to Calendar
            </a>
        </div>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="rounded-lg border border-[#516070] bg-white p-5 shadow-sm">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold text-gw-primary" style="background-color: {{ $event->display_color }}33; border: 1px solid {{ $event->display_color }}">{{ $event->type?->name ?? 'Event' }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($event->status)->headline() }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($event->visibility)->headline() }}</span>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gw-primary">Description</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $event->description ?: 'No description has been added yet.' }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</p>
                            <p class="mt-1 text-sm font-semibold text-gw-primary">{{ $event->location ?: 'Virtual or not set' }}</p>
                        </div>
                        <div class="rounded-lg border border-[#516070]/20 bg-[#F8FAFC] p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Meeting Link</p>
                            @if ($event->meeting_link)
                                <a href="{{ $event->meeting_link }}" class="mt-1 block truncate text-sm font-semibold text-gw-primary underline">{{ $event->meeting_link }}</a>
                            @else
                                <p class="mt-1 text-sm font-semibold text-gw-primary">Not set</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-[#516070] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Organizer</p>
                    <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $event->organizer?->name }}</p>
                    <p class="text-xs text-slate-500">{{ $event->organizer?->email }}</p>
                </div>

                @if ($event->relatedProspect)
                    <div class="rounded-lg border border-[#516070] bg-white p-4 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Linked Prospect</p>
                        <a href="{{ route('team.prospects.records.show', $event->relatedProspect) }}" class="mt-2 block text-sm font-semibold text-gw-primary hover:text-gw-accent-dark">
                            {{ $event->relatedProspect->displayName() }}
                        </a>
                        <p class="mt-1 text-xs text-slate-500">{{ str($event->relatedProspect->funnel_type ?? 'insurance')->headline() }}{{ $event->relatedProspect->stage ? ' - '.$event->relatedProspect->stage->name : '' }}</p>
                    </div>
                @endif

                <div class="rounded-lg border border-[#516070] bg-white p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Attendees</p>
                    <div class="mt-3 space-y-2">
                        @forelse ($event->attendees as $attendee)
                            <div class="rounded-md bg-[#F8FAFC] px-3 py-2">
                                <div class="text-sm font-semibold text-gw-primary">{{ $attendee->user?->name ?? $attendee->name ?? $attendee->email }}</div>
                                <div class="text-xs text-slate-500">{{ str($attendee->rsvp_status)->headline() }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No attendees yet.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
