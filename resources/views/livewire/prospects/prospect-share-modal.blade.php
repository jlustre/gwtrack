<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>
            <div class="relative z-10 w-full max-w-lg rounded-lg border border-gw-accent/40 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gw-primary">Share Prospect</h3>
                    <button type="button" wire:click="close" class="text-slate-500 hover:text-slate-700">&times;</button>
                </div>

                <form wire:submit="save" class="grid gap-3">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Visibility Preset</span>
                        <select wire:model.live="preset" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($visibilityPresets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('preset') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </label>

                    @if ($preset === 'user')
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Search User</span>
                            <input wire:model.live.debounce.300ms="userSearch" type="search" placeholder="Name or email…" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @error('explicitUserId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        @if ($selectedUser)
                            <p class="text-sm text-gw-primary">Selected: <strong>{{ $selectedUser->name }}</strong> ({{ $selectedUser->email }})</p>
                        @endif
                        @if ($userResults->isNotEmpty())
                            <ul class="rounded-lg border border-slate-200 bg-white text-sm">
                                @foreach ($userResults as $user)
                                    <li>
                                        <button type="button" wire:click="$set('explicitUserId', {{ $user->id }})" class="block w-full px-3 py-2 text-left hover:bg-gw-accent-soft">
                                            {{ $user->name }} <span class="text-slate-500">{{ $user->email }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Permission Level</span>
                        <select wire:model="permissionId" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($permissions as $permission)
                                <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Expires (optional)</span>
                        <input wire:model="expiresAt" type="date" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        @error('expiresAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </label>

                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Save Sharing</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
