<div class="space-y-4">
    @if ($feedbackMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ $feedbackMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($canSchedule)
        <form wire:submit="schedule" class="space-y-4 rounded-lg border border-gw-accent/40 bg-gw-accent-soft p-4">
            <div>
                <h3 class="text-sm font-semibold text-gw-primary">Schedule Client FNA Review</h3>
                <p class="mt-1 text-xs text-slate-600">Creates a calendar event and updates the linked prospect timeline.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Meeting Type</span>
                    <select wire:model="meetingType" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($meetingTypes as $type)
                            <option value="{{ $type['slug'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date & Time</span>
                    <input wire:model="startsAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    @error('startsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duration (minutes)</span>
                    <input wire:model="durationMinutes" type="number" min="15" max="480" step="15" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                </label>

                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Location or Meeting Link</span>
                    <input wire:model="locationOrLink" type="text" placeholder="Office address or Zoom link" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                </label>

                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</span>
                    <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                </label>
            </div>

            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-[#132d52]">
                {{ $fna->calendar_event_id ? 'Update Meeting' : 'Schedule Meeting' }}
            </button>
        </form>
    @endif

    @if ($fna->calendarEvent)
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
            <p class="font-semibold text-gw-primary">Scheduled Meeting</p>
            <p class="mt-1 text-slate-700">{{ $fna->calendarEvent->starts_at?->format('M j, Y g:i A') }} · {{ $fna->calendarEvent->type?->name ?? 'FNA Meeting' }}</p>
            @if ($fna->calendarEvent->meeting_link)
                <p class="mt-1 text-xs text-gw-accent-dark">{{ $fna->calendarEvent->meeting_link }}</p>
            @elseif ($fna->calendarEvent->location)
                <p class="mt-1 text-xs text-slate-600">{{ $fna->calendarEvent->location }}</p>
            @endif
        </div>
    @endif
</div>
