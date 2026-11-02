<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin Management"
        title="Subscription Payments"
        description="Collect monthly GWTracks subscription invoices, record office payments, and keep paid history next to each associate."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.settings') }}"
                class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20"
            >
                Rank prices
            </a>
        </x-slot:actions>
    </x-admin.page-hero>

    @if (session('payments_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('payments_status') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-tracker-stat-card label="Open invoices" :value="$stats['outstanding_count']" :subtitle="'$'.number_format($stats['outstanding_amount'], 2).' due'" theme="amber" />
        <x-tracker-stat-card label="Amount due" :value="'$'.number_format($stats['outstanding_amount'], 2)" subtitle="Issued and unpaid" theme="red" />
        <x-tracker-stat-card label="Paid this month" :value="$stats['paid_this_month_count']" :subtitle="'$'.number_format($stats['paid_this_month_amount'], 2)" theme="emerald" />
        <x-tracker-stat-card label="Collected total" :value="'$'.number_format($stats['collected_amount'], 2)" subtitle="All paid invoices" theme="navy" />
    </div>

    @if ($showRecordForm)
        <section class="overflow-hidden rounded-lg border border-gw-accent/40 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Record payment</p>
                <h2 class="mt-1 text-lg font-semibold">{{ $recordingNumber }} · {{ $recordingAmountLabel }}</h2>
            </div>
            <form wire:submit="recordPayment" class="grid gap-4 p-6 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Method</label>
                    <select wire:model="paymentMethod" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($methods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('paymentMethod') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</label>
                    <input
                        type="text"
                        wire:model="paymentReference"
                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        placeholder="Check #, last four, transfer ID"
                    >
                    @error('paymentReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                    <textarea
                        wire:model="paymentNotes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        placeholder="Optional office note"
                    ></textarea>
                    @error('paymentNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <button type="submit" class="rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                        Save payment
                    </button>
                    <button type="button" wire:click="cancelRecordForm" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gw-primary">Invoice queue</h2>
                <p class="mt-1 text-sm text-slate-600">Search by associate, email, or invoice number. Record cash, check, card, or bank payments from the office.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-sm">
                    @foreach (['outstanding' => 'Due', 'paid' => 'Paid', 'all' => 'All'] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('tab', '{{ $key }}')"
                            @class([
                                'rounded-md px-3 py-1.5 font-semibold',
                                'bg-white text-gw-primary shadow-sm' => $tab === $key,
                                'text-slate-600 hover:text-gw-primary' => $tab !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search invoices"
                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent sm:w-64"
                >
            </div>
        </div>

        @if ($invoices->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gw-primary">No invoices in this view</p>
                <p class="mt-2 text-sm text-slate-600">
                    @if ($tab === 'outstanding')
                        Nothing is due right now. New invoices appear when licensed associates reach a billed rank.
                    @else
                        Try another filter or search term.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Invoice</th>
                            <th class="px-6 py-3">Associate</th>
                            <th class="px-6 py-3">Period</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                            <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($invoices as $invoice)
                            <tr class="hover:bg-slate-50" wire:key="payment-invoice-{{ $invoice->id }}">
                                <td class="px-6 py-3">
                                    <p class="font-semibold text-gw-primary">{{ $invoice->number }}</p>
                                    <p class="text-xs text-slate-500">{{ $invoice->rank_label }} · {{ $invoice->billed_at->format('M j, Y') }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="font-semibold text-slate-800">{{ $invoice->user?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $invoice->user?->email }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ $invoice->periodLabel() }}</td>
                                <td class="px-6 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-50 text-amber-800' => $invoice->status === 'issued',
                                        'bg-emerald-50 text-emerald-800' => $invoice->status === 'paid',
                                        'bg-slate-100 text-slate-600' => ! in_array($invoice->status, ['issued', 'paid'], true),
                                    ])>{{ $invoice->statusLabel() }}</span>
                                    @if ($invoice->payment_reference)
                                        <p class="mt-1 text-xs text-slate-500">{{ $invoice->payment_reference }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-gw-primary">{{ $invoice->amountLabel() }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('billing.invoices.show', $invoice) }}" class="text-xs font-semibold text-gw-accent-dark hover:underline">View</a>
                                        @if ($invoice->isPayable())
                                            <button type="button" wire:click="openRecordForm({{ $invoice->id }})" class="text-xs font-semibold text-gw-primary hover:underline">
                                                Record payment
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="voidInvoice({{ $invoice->id }})"
                                                wire:confirm="Void invoice {{ $invoice->number }}? This cannot be collected later."
                                                class="text-xs font-semibold text-red-600 hover:underline"
                                            >
                                                Void
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </section>
</div>
