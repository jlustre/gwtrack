<div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
    <form wire:submit="save" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="schedule-phone-call-name" class="text-sm font-semibold text-gw-primary">Who are you calling?</label>
                <input id="schedule-phone-call-name" type="text" wire:model="contactName" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" @readonly($personType !== 'external')>
                @error('contactName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="schedule-phone-call-phone" class="text-sm font-semibold text-gw-primary">Phone number</label>
                <input id="schedule-phone-call-phone" type="text" wire:model="contactPhone" placeholder="6045550100" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('contactPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="schedule-phone-call-when" class="text-sm font-semibold text-gw-primary">When to call</label>
                <input id="schedule-phone-call-when" type="datetime-local" wire:model="scheduledAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('scheduledAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="schedule-phone-call-email" class="text-sm font-semibold text-gw-primary">Email (optional)</label>
                <input id="schedule-phone-call-email" type="email" wire:model="contactEmail" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('contactEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="schedule-phone-call-purpose" class="text-sm font-semibold text-gw-primary">Purpose</label>
            <input id="schedule-phone-call-purpose" type="text" wire:model="purpose" placeholder="Follow up on presentation, onboarding check-in..." class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
        </div>

        <div>
            <label for="schedule-phone-call-notes" class="text-sm font-semibold text-gw-primary">Notes</label>
            <textarea id="schedule-phone-call-notes" wire:model="notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
        </div>

        <div class="flex flex-wrap gap-4 text-sm text-slate-700">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model="syncCalendar" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                Add to calendar
            </label>
            @if ($prospectId && ! $isEditing)
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="createPrepTask" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                    Create prep task
                </label>
            @endif
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Save changes' : 'Schedule phone call' }}</span>
                <span wire:loading wire:target="save">{{ $isEditing ? 'Saving…' : 'Scheduling…' }}</span>
            </button>
        </div>
    </form>
</div>
