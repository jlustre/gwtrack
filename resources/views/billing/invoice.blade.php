<x-app-layout>
    @php
        $profile = $associate->profile;
    @endphp

    @if (session('billing_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 print:hidden">
            {{ session('billing_status') }}
        </div>
    @endif

    <div class="space-y-6 print:space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a
                href="{{ route('billing.index') }}"
                wire:navigate
                class="text-sm font-semibold text-gw-accent-dark hover:underline"
            >
                ← Back to My Billing
            </a>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent"
                    onclick="window.print()"
                >
                    Print invoice
                </button>
                @if ($invoice->isPayable())
                    <a
                        href="{{ route('billing.invoices.pay', $invoice) }}"
                        class="rounded-md bg-gw-accent px-4 py-2 text-sm font-extrabold uppercase tracking-wide text-gw-primary hover:bg-gw-accent-hover"
                    >
                        Pay {{ $invoice->amountLabel() }}
                    </a>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm print:border-0 print:shadow-none">
            <div class="bg-gw-primary px-6 py-6 text-white print:bg-white print:text-slate-900 print:px-0">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent print:text-slate-500">GWTrack invoice</p>
                        <h1 class="mt-2 text-2xl font-semibold">{{ $invoice->number }}</h1>
                        <p class="mt-2 text-sm text-slate-200 print:text-slate-600">{{ $invoice->description }}</p>
                    </div>
                    <div class="text-sm sm:text-right">
                        <p class="font-semibold">Issued {{ $invoice->billed_at->format('M j, Y') }}</p>
                        <p class="mt-1">{{ $invoice->statusLabel() }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 px-6 py-6 sm:grid-cols-2 print:px-0">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Billed to</p>
                    <p class="mt-2 font-semibold text-gw-primary">{{ $associate->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $associate->email }}</p>
                    @if ($profile?->gw_associate_id)
                        <p class="mt-1 text-sm text-slate-600">GW Associate ID {{ $profile->gw_associate_id }}</p>
                    @endif
                    @if ($associate->rank)
                        <p class="mt-1 text-sm text-slate-600">Current rank {{ $associate->rank->name }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Billing period</p>
                    <p class="mt-2 font-semibold text-gw-primary">{{ $invoice->periodLabel() }}</p>
                    <p class="mt-1 text-sm text-slate-600">Plan: {{ $invoice->intervalLabel() }}</p>
                    <p class="mt-1 text-sm text-slate-600">Rank at billing: {{ $invoice->rank_label }}</p>
                    <p class="mt-1 text-sm text-slate-600">Currency {{ $invoice->currency }}</p>
                </div>
            </div>

            <div class="overflow-x-auto px-6 pb-6 print:px-0">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-4 text-slate-700">Monthly GWTrack subscription</td>
                            <td class="px-4 py-4 text-slate-600">{{ $invoice->rank_code }}</td>
                            <td class="px-4 py-4 text-right font-semibold text-gw-primary">{{ $invoice->amountLabel() }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td class="px-4 py-4 text-sm font-semibold text-slate-700" colspan="2">{{ $invoice->isPaid() ? 'Total paid' : 'Total due' }}</td>
                            <td class="px-4 py-4 text-right text-lg font-semibold text-gw-primary">{{ $invoice->amountLabel() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($invoice->isPaid() || $invoice->status === 'complimentary')
                <div class="border-t border-slate-100 px-6 py-5 print:px-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</p>
                    <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $invoice->statusLabel() }}{{ $invoice->paid_at ? ' on '.$invoice->paid_at->format('M j, Y') : '' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $invoice->paymentMethodLabel() }}@if ($invoice->payment_reference) · {{ $invoice->payment_reference }}@endif</p>
                    @if ($invoice->payer)
                        <p class="mt-1 text-sm text-slate-600">Recorded by {{ $invoice->payer->name }}</p>
                    @endif
                    @if ($invoice->payment_notes)
                        <p class="mt-2 text-sm text-slate-600">{{ $invoice->payment_notes }}</p>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
