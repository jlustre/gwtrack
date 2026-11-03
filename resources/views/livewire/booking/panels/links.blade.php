<section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
    @if ($linkableEventTypes->isNotEmpty() && $linkableSchedules->isNotEmpty())
        <form wire:submit="createBookingLink" class="mb-4 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-3 sm:items-end">
            <div>
                <label for="link-name" class="block text-xs font-semibold text-slate-600">Link name</label>
                <input id="link-name" wire:model="linkName" required maxlength="120" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                @error('linkName') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="link-event-type" class="block text-xs font-semibold text-slate-600">Event type</label>
                <select id="link-event-type" wire:model="linkEventTypeId" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Choose an event type</option>
                    @foreach ($linkableEventTypes as $type)<option value="{{ $type->id }}">{{ $type->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="link-schedule" class="block text-xs font-semibold text-slate-600">Availability schedule</label>
                <select id="link-schedule" wire:model="linkScheduleId" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Choose a schedule</option>
                    @foreach ($linkableSchedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->name }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-3"><button class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Create booking link</button></div>
        </form>
    @else
        <p class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">Create an active event type and availability schedule before making a booking link.</p>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                <tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Type</th><th class="px-3 py-2">Event</th><th class="px-3 py-2">Uses</th><th class="px-3 py-2">Link</th><th class="px-3 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($links as $link)
                    <tr wire:key="booking-link-{{ $link->id }}">
                        <td class="px-3 py-2 font-semibold text-gw-primary">{{ $link->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ str($link->link_type)->headline() }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $link->eventType?->title ?? 'Personal page' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $link->uses_count }}{{ $link->max_uses ? ' / '.$link->max_uses : '' }}</td>
                        <td class="px-3 py-2"><a class="font-semibold text-gw-accent-dark" href="{{ route('bookings.invite', $link->token) }}" target="_blank" rel="noopener">Open</a></td>
                        <td class="px-3 py-2"><button type="button" wire:click="toggleBookingLink({{ $link->id }})" class="font-semibold text-gw-accent-dark">{{ $link->is_active ? 'Deactivate' : 'Activate' }}</button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No booking links yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
