<section class="rounded-lg border border-slate-300 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">CRM forecast</p>
            <h2 class="mt-1 text-lg font-semibold text-gw-primary">Opportunity Management</h2>
            <p class="mt-1 text-sm text-slate-600">Track expected value and timing without changing the prospect’s funnel stage.</p>
        </div>
        @if ($salesOpportunity?->status === 'won' || $recruitingOpportunity?->status === 'won')
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Won</span>
        @elseif ($salesOpportunity?->status === 'lost' || $recruitingOpportunity?->status === 'lost')
            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Closed lost</span>
        @else
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Open</span>
        @endif
    </div>

    @if (session('opportunity-status'))
        <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('opportunity-status') }}
        </div>
    @endif

    <form wire:submit="save" class="mt-5 space-y-5">
        @if ($salesOpportunity)
            <fieldset class="rounded-lg border border-slate-200 p-4">
                <legend class="px-2 text-sm font-semibold text-gw-primary">Sales opportunity</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm font-medium text-slate-700">
                        Estimated value
                        <input type="number" min="0" step="0.01" wire:model="estimatedValue" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @error('estimated_value') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Probability (%)
                        <input type="number" min="0" max="100" step="1" wire:model="salesProbability" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @error('sales_probability') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Expected close
                        <input type="date" wire:model="expectedCloseAt" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @error('expected_close_at') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                </div>
                @if ($salesOpportunity->estimated_value !== null)
                    <p class="mt-3 text-xs text-slate-500">
                        Weighted forecast:
                        <span class="font-semibold text-slate-700">{{ number_format((float) $salesOpportunity->estimated_value * (int) $salesOpportunity->probability / 100, 2) }}</span>
                    </p>
                @endif
            </fieldset>
        @endif

        @if ($recruitingOpportunity)
            <fieldset class="rounded-lg border border-slate-200 p-4">
                <legend class="px-2 text-sm font-semibold text-gw-primary">Recruiting opportunity</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">
                        Probability (%)
                        <input type="number" min="0" max="100" step="1" wire:model="recruitingProbability" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @error('recruiting_probability') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Expected start
                        <input type="date" wire:model="expectedStartAt" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @error('expected_start_at') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                </div>
            </fieldset>
        @endif

        @if ($salesOpportunity || $recruitingOpportunity)
            <label class="block text-sm font-medium text-slate-700">
                Lost reason
                <input type="text" maxlength="255" wire:model="lostReason" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm" placeholder="Optional context if this relationship closes lost">
                @error('lost_reason') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
            </label>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary/90 disabled:opacity-50">
                    Save forecast
                </button>
            </div>
        @else
            <p class="rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">No sales or recruiting opportunity is associated with this prospect’s funnel.</p>
        @endif
    </form>
</section>
