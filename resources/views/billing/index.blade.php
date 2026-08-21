<x-app-layout>
    @php
        $nextBillingLabel = $snapshot['next_billing_at']
            ? \Illuminate\Support\Carbon::parse($snapshot['next_billing_at'])->format('M j, Y')
            : 'Not scheduled';
        $startedLabel = $snapshot['started_at']
            ? \Illuminate\Support\Carbon::parse($snapshot['started_at'])->format('M j, Y')
            : null;
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            <div class="bg-gw-primary px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">My Workspace</p>
                        <h1 class="mt-2 text-2xl font-semibold">My Billing</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                            Review your GWTrack subscription, pay open invoices, and keep a record of every charge issued to this associate account.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        @if ($outstandingCount > 0)
                            @php $nextDue = $invoices->firstWhere('status', 'issued'); @endphp
                            @if ($nextDue)
                                <a
                                    href="{{ route('billing.invoices.pay', $nextDue) }}"
                                    class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-extrabold uppercase tracking-wide text-gw-primary hover:bg-gw-accent-hover"
                                >
                                    Pay {{ $nextDue->amountLabel() }} due
                                </a>
                            @endif
                        @endif
                        <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Status</p>
                            <p class="mt-1 font-semibold">{{ $snapshot['status_label'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 bg-white px-6 py-4">
                <p class="text-sm leading-6 text-slate-600">{{ $snapshot['status_detail'] }}</p>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current rank</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $snapshot['current_rank_label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $snapshot['licensed'] ? 'Licensed associate' : 'License not on file' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $snapshot['billing_interval'] === 'annual' ? 'Annual amount' : 'Monthly amount' }}</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $snapshot['billed_amount_label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $snapshot['billed_rank_label'] ? 'Billed as '.$snapshot['billed_rank_label'] : 'No billed rank yet' }}
                    @if ($snapshot['billing_interval'] === 'annual')
                        · {{ $snapshot['monthly_amount_label'] }}/mo
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Next billing date</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">{{ $nextBillingLabel }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($snapshot['pending_rank'] && $snapshot['pending_rank'] !== $snapshot['billed_rank'])
                        Then billed as {{ $snapshot['pending_rank_label'] }} ({{ $snapshot['pending_amount_label'] }})
                    @elseif ($startedLabel)
                        Billing started {{ $startedLabel }}
                    @else
                        No anniversary on file
                    @endif
                </p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount due</p>
                <p class="mt-2 text-lg font-semibold text-gw-primary">${{ number_format($outstandingAmount, 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $outstandingCount === 0 ? 'No open invoices' : $outstandingCount.' open invoice'.($outstandingCount === 1 ? '' : 's') }}
                    · Collected ${{ number_format($totalCollected, 2) }}
                </p>
            </div>
        </section>

        @if (session('billing_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('billing_status') }}
            </div>
        @endif

        @if ($snapshot['billable'] || $snapshot['monthly_amount'] > 0 || $snapshot['billed_rank'])
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gw-primary">Payment plan</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Choose automatic monthly charges or one annual invoice. A plan change updates an unpaid invoice right away; if this period is already paid, it starts on the next billing date.
                    </p>
                </div>
                <form method="POST" action="{{ route('billing.plan.update') }}" class="grid gap-4 p-6 md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    @php
                        $monthlySelected = ($snapshot['plan_key'] ?? 'monthly_auto') === 'monthly_auto';
                        $annualSelected = ($snapshot['plan_key'] ?? '') === 'annual';
                    @endphp
                    <button
                        type="submit"
                        name="plan"
                        value="monthly_auto"
                        @class([
                            'rounded-lg border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                            'border-gw-accent bg-gw-accent-soft ring-2 ring-gw-accent' => $monthlySelected,
                            'border-slate-200 bg-white hover:border-gw-accent/60' => ! $monthlySelected,
                        ])
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide {{ $monthlySelected ? 'text-gw-accent-dark' : 'text-slate-500' }}">Monthly auto-pay</p>
                        <p class="mt-2 text-2xl font-semibold text-gw-primary">{{ $snapshot['monthly_amount_label'] }}<span class="text-base font-medium text-slate-500"> / month</span></p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Charged automatically each month after you pay once with a card.
                            @if ($snapshot['auto_pay_ready'])
                                Card on file: {{ $snapshot['auto_pay_card_label'] }}.
                            @elseif ($snapshot['auto_pay'])
                                Pay an invoice to save a card and start automatic charges.
                            @endif
                        </p>
                        @if ($monthlySelected)
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gw-primary">Selected</p>
                        @endif
                    </button>
                    <button
                        type="submit"
                        name="plan"
                        value="annual"
                        @class([
                            'rounded-lg border p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                            'border-gw-accent bg-gw-accent-soft ring-2 ring-gw-accent' => $annualSelected,
                            'border-slate-200 bg-white hover:border-gw-accent/60' => ! $annualSelected,
                        ])
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide {{ $annualSelected ? 'text-gw-accent-dark' : 'text-slate-500' }}">Annual</p>
                        <p class="mt-2 text-2xl font-semibold text-gw-primary">{{ $snapshot['annual_amount_label'] }}<span class="text-base font-medium text-slate-500"> / year</span></p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            One invoice for twelve months of the current rank price. Rank changes still wait until the next billing date.
                        </p>
                        @if ($annualSelected)
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gw-primary">Selected</p>
                        @endif
                    </button>
                </form>
                @if ($snapshot['pending_billing_interval_label'])
                    <p class="border-t border-slate-100 px-6 py-3 text-sm text-slate-600">
                        {{ $snapshot['pending_billing_interval_label'] }} billing starts {{ $nextBillingLabel }}.
                    </p>
                @endif
            </section>
        @endif

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Invoice history</h2>
                    <p class="mt-1 text-sm text-slate-600">Pay open invoices here or from the invoice page. Paid receipts stay in this history.</p>
                </div>
            </div>

            @if ($invoices->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-semibold text-gw-primary">No invoices yet</p>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">
                        @if (! $snapshot['enabled'])
                            Billing is off, so GWTrack will not issue invoices.
                        @elseif (! $snapshot['licensed'])
                            Invoices start after your life license is on file and you reach a billed rank.
                        @elseif ($snapshot['monthly_amount'] <= 0)
                            Your current rank is free. Invoices appear when you advance to Senior Associate or above.
                        @else
                            Your first invoice will appear here as soon as the subscription charge is issued.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Invoice</th>
                                <th class="px-6 py-3">Billed</th>
                                <th class="px-6 py-3">Rank</th>
                                <th class="px-6 py-3">Plan</th>
                                <th class="px-6 py-3">Period</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                                <th class="px-6 py-3"><span class="sr-only">View</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoices as $invoice)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-semibold text-gw-primary">{{ $invoice->number }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $invoice->billed_at->format('M j, Y') }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $invoice->rank_label }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $invoice->intervalLabel() }}</td>
                                    <td class="px-6 py-3 text-slate-600">{{ $invoice->periodLabel() }}</td>
                                    <td class="px-6 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-amber-50 text-amber-800' => $invoice->status === 'issued',
                                            'bg-emerald-50 text-emerald-800' => $invoice->status === 'paid',
                                            'bg-slate-100 text-slate-600' => ! in_array($invoice->status, ['issued', 'paid'], true),
                                        ])>{{ $invoice->statusLabel() }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-gw-primary">{{ $invoice->amountLabel() }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            @if ($invoice->isPayable())
                                                <a
                                                    href="{{ route('billing.invoices.pay', $invoice) }}"
                                                    class="text-sm font-semibold text-gw-primary hover:underline"
                                                >
                                                    Pay now
                                                </a>
                                            @endif
                                            <a
                                                href="{{ route('billing.invoices.show', $invoice) }}"
                                                wire:navigate
                                                class="text-sm font-semibold text-gw-accent-dark hover:underline"
                                            >
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
