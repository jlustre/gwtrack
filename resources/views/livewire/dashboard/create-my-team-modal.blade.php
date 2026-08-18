<div>
    @if ($show)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="create-my-team-modal-title"
        >
            <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>

            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-gw-accent/40 bg-white shadow-xl">
                <div class="border-b border-gw-primary/20 bg-gradient-to-br from-gw-primary via-[#243846] to-gw-primary px-5 py-4 text-white">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">My Team</p>
                            <h3 id="create-my-team-modal-title" class="mt-1 text-lg font-semibold">Create My Team</h3>
                            <p class="mt-1 text-xs text-slate-200">
                                Name your team. You become the leader, and everyone in your recruit hierarchy is moved onto it automatically.
                            </p>
                        </div>
                        <button type="button" wire:click="close" class="gw-icon-btn-overlay" aria-label="Close">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="create" class="space-y-4 p-5">
                    <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft/60 px-4 py-3 text-sm text-gw-primary">
                        <p class="font-semibold">Hierarchy preview</p>
                        <p class="mt-1 text-slate-600">
                            @if ($hierarchyCount > 0)
                                {{ $hierarchyCount }} recruit{{ $hierarchyCount === 1 ? '' : 's' }} in your downline will become members of this team (plus you as leader).
                            @else
                                No recruits yet — the team will be created with you as leader. Future recruits you sponsor can join this team.
                            @endif
                        </p>
                    </div>

                    <div>
                        <label for="create-my-team-name" class="text-sm font-semibold text-gw-primary">Team name</label>
                        <input
                            id="create-my-team-name"
                            type="text"
                            wire:model="name"
                            maxlength="120"
                            placeholder="e.g. Santos Growth Team"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            autofocus
                        >
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="create-my-team-description" class="text-sm font-semibold text-gw-primary">Description <span class="font-normal text-slate-500">(optional)</span></label>
                        <textarea
                            id="create-my-team-description"
                            wire:model="description"
                            rows="3"
                            maxlength="1000"
                            placeholder="What this team focuses on…"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="close" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="create">Create team</span>
                            <span wire:loading wire:target="create">Creating…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
