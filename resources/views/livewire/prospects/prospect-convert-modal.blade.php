<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>
            <div class="relative z-10 w-full max-w-xl rounded-lg border border-gw-accent/40 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gw-primary">Convert Prospect</h3>
                        @if ($prospect)
                            <p class="mt-1 text-sm text-slate-600">{{ $prospect->displayName() }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="close" class="text-slate-500 hover:text-slate-700">&times;</button>
                </div>

                <div class="mb-4 flex gap-2 border-b border-slate-200 pb-3">
                    <button type="button" wire:click="$set('tab', 'associate')" @class([
                        'rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                        $tab === 'associate' ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-100',
                    ])>Associate</button>
                    <button type="button" wire:click="$set('tab', 'client')" @class([
                        'rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                        $tab === 'client' ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-100',
                    ])>Client</button>
                    <button type="button" wire:click="$set('tab', 'inactive')" @class([
                        'rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                        $tab === 'inactive' ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-100',
                    ])>Inactive</button>
                </div>

                @if ($tab === 'associate')
                    @if ($prospect && blank($prospect->email))
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            This prospect has no email on file. The registration link will not be restricted to a specific address.
                        </div>
                    @elseif ($prospect?->email)
                        <p class="mb-4 text-sm text-slate-600">Invitation email: <strong>{{ $prospect->email }}</strong></p>
                    @endif

                    @if ($invitationUrl)
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            <p class="font-semibold">{{ $statusMessage }}</p>
                            <p class="mt-2 break-all font-mono text-xs">{{ $invitationUrl }}</p>
                        </div>
                    @else
                        <form wire:submit="convertAssociate" class="grid gap-3">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Notes (optional)</span>
                                <textarea wire:model="associateNotes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                                @error('associateNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </label>

                            <div class="mt-2 flex justify-end gap-2">
                                <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                                <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Create Invitation</button>
                            </div>
                        </form>
                    @endif

                    @if ($invitationUrl)
                        <div class="mt-4 flex justify-end">
                            <button type="button" wire:click="close" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Done</button>
                        </div>
                    @endif
                @elseif ($tab === 'client')
                    <form wire:submit="convertClient" class="grid gap-3">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Policy Reference <span class="text-red-600">*</span></span>
                            <input wire:model="clientPolicyReference" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @error('clientPolicyReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Application Reference</span>
                            <input wire:model="clientApplicationReference" type="text" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @error('clientApplicationReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Notes</span>
                            <textarea wire:model="clientNotes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                            @error('clientNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>

                        <div class="mt-2 flex justify-end gap-2">
                            <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Convert to Client</button>
                        </div>
                    </form>
                @else
                    <form wire:submit="convertInactive" class="grid gap-3">
                        <p class="text-sm text-slate-600">Archive this prospect and record why they are no longer active in your pipeline.</p>

                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Reason (optional)</span>
                            <textarea wire:model="inactiveReason" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                            @error('inactiveReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>

                        <div class="mt-2 flex justify-end gap-2">
                            <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Mark Inactive</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
