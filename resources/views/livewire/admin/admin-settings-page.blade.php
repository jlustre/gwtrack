<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin Management"
        title="Admin Settings"
        description="Control portal-wide billing. Rank prices apply only to licensed associates. Training Associate and Associate ranks stay free, and unlicensed associates are never charged."
    >
        <x-slot:actions>
            @if ($monetizationEnabled)
                <a
                    href="{{ route('admin.payments') }}"
                    class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20"
                >
                    Process payments
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('status'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-gw-primary to-[#243846] px-6 py-5 text-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Subscriptions</p>
            <h2 class="mt-1 text-xl font-semibold">Rank-based monthly billing</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">
                When billing is on, licensed SA and above are billed at the prices below. Associates can choose monthly auto-pay or one annual invoice (twelve months). A rank change does not change the billed amount until the next billing date.
            </p>
        </div>

        <div class="space-y-8 p-6">
            <section class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gw-primary">Enable monetization</p>
                    <p class="mt-1 text-sm text-slate-600">Turn monthly rank subscriptions on or off for the whole portal.</p>
                </div>
                <label class="inline-flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="monetizationEnabled"
                        @disabled(! $canEdit)
                        class="h-5 w-5 rounded border-slate-300 text-gw-accent focus:ring-gw-accent disabled:opacity-60"
                    >
                    <span class="text-sm font-semibold text-gw-primary">{{ $monetizationEnabled ? 'On' : 'Off' }}</span>
                </label>
            </section>

            <section>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gw-accent-dark">Monthly prices</h3>
                <p class="mt-1 text-sm text-slate-600">Amounts are USD per month. TA and ASC remain $0.00. Unlicensed associates are free at every rank.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($priceGroups as $group)
                        <label class="block rounded-lg border border-slate-200 bg-white p-4">
                            <span class="text-sm font-semibold text-gw-primary">{{ $group['label'] }}</span>
                            <span class="mt-1 block text-xs text-slate-500">{{ implode(', ', $group['codes']) }}</span>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-500">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="999.99"
                                    wire:model="prices.{{ $group['key'] }}"
                                    @disabled(! $canEdit)
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-50"
                                >
                            </div>
                            <x-input-error :messages="$errors->get('prices.'.$group['key'])" class="mt-1" />
                        </label>
                    @endforeach
                </div>
            </section>

            @if ($canEdit)
                <div class="flex justify-end">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="save">Save settings</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            @else
                <p class="text-sm text-slate-500">Only super admins and admins can change monetization settings.</p>
            @endif
        </div>
    </form>
</div>
