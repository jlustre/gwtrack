<div @class(['space-y-6' => ! $embedded, 'space-y-3' => $embedded])>
    @unless ($embedded)
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="bg-gw-primary px-6 py-6 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Add Prospect</h1>
                <p class="mt-2 text-sm text-slate-200">Create a new private prospect record and place them in your sales funnel.</p>
            </div>
        </div>
    @else
        <div class="flex items-center justify-between gap-3 rounded-md px-3 py-2.5 text-white {{ $quick ? 'bg-emerald-800' : 'bg-gw-primary' }}">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold">{{ $quick ? 'Quick Add Prospect' : 'Add Prospect' }}</h2>
                <p class="truncate text-xs text-slate-300">
                    {{ $quick ? 'Essential fields only · add and keep moving' : 'New CRM record · funnel, contact, and qualification details' }}
                </p>
            </div>
            <button
                type="button"
                @if ($modal)
                    wire:click="$dispatch('close-dashboard-prospect-quick-add')"
                @else
                    x-on:click="{{ $quick ? 'showQuickAddProspect = false' : 'showCreateProspect = false' }}"
                @endif
                class="shrink-0 rounded border border-white/30 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-white/10"
            >
                Close
            </button>
        </div>
    @endunless

    @if (! $setupAvailable)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Prospect funnel setup is not available yet. Seed the CRM lookup data before creating prospects.
        </div>
    @else
        <form wire:submit="save" @class(['rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-4 shadow-sm sm:p-5' => ! $embedded, 'rounded-md border border-slate-200 bg-white p-2.5 shadow-sm sm:p-3' => $embedded])>
            @if ($quick)
                @include('livewire.prospects.partials.quick-form-fields')
            @else
                @include('livewire.prospects.partials.form-fields', ['compact' => $embedded])
            @endif

            <div @class(['mt-4 flex flex-wrap items-center justify-end gap-2' => ! $embedded, 'mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-2.5' => $embedded])>
                @if ($embedded)
                    <button
                        type="button"
                        @if ($modal)
                            wire:click="$dispatch('close-dashboard-prospect-quick-add')"
                        @else
                            x-on:click="{{ $quick ? 'showQuickAddProspect = false' : 'showCreateProspect = false' }}"
                        @endif
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    @if ($quick && ! $modal)
                        <button
                            type="button"
                            x-on:click="showQuickAddProspect = false; showCreateProspect = true; $nextTick(() => $refs.prospectCreatePanel?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Full form
                        </button>
                    @endif
                @else
                    <a href="{{ route('team.prospects') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                @endif
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                    {{ $quick ? 'Quick Add' : 'Create Prospect' }}
                </button>
            </div>
        </form>
    @endif
</div>
