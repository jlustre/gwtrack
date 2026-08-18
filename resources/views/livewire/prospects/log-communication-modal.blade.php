<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>
            <div class="relative z-10 w-full max-w-lg rounded-lg border border-gw-accent/40 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gw-primary">Log Communication</h3>
                    <button type="button" wire:click="close" class="text-slate-500 hover:text-slate-700">&times;</button>
                </div>

                <form wire:submit="save" class="grid gap-3">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Type</span>
                        <select wire:model="communication_type_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Select type</option>
                            @foreach ($communicationTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Direction</span>
                        <select wire:model="direction" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            <option value="outbound">Outbound</option>
                            <option value="inbound">Inbound</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Contacted At</span>
                        <input wire:model="contacted_at" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @error('contacted_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Outcome</span>
                        <input wire:model="outcome" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Notes</span>
                        <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Duration (minutes)</span>
                        <input wire:model="duration_minutes" type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Save Communication</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
