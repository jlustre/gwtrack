@if ($event)
    <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Event details</h2>
        <dl class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">When</dt>
                <dd class="font-medium">{{ $event->starts_at?->format('M j, Y g:i A') }}</dd>
            </div>
            @if ($event->location)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Location</dt>
                    <dd class="font-medium">{{ $event->location }}</dd>
                </div>
            @endif
            @if ($event->meeting_link)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Meeting link</dt>
                    <dd><a href="{{ $event->meeting_link }}" class="font-semibold text-gw-accent-dark hover:underline" target="_blank" rel="noopener">{{ $event->meeting_link }}</a></dd>
                </div>
            @endif
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Registered</dt>
                <dd class="font-medium">{{ $attendeeCount }} attending</dd>
            </div>
        </dl>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            @if ($rsvpStatus === 'accepted')
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">You are registered</span>
            @elseif ($rsvpStatus === 'declined')
                <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">You declined</span>
                <button type="button" wire:click="accept" class="rounded-lg bg-gw-primary px-3 py-1.5 text-xs font-semibold text-gw-accent">Register instead</button>
            @else
                <button type="button" wire:click="accept" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent">Register / RSVP</button>
                <button type="button" wire:click="decline" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Decline</button>
            @endif
            <a href="{{ route('calendar.events.show', $event) }}" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">View in calendar →</a>
        </div>
    </div>
@endif
