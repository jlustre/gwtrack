<x-app-layout>
    @php
        $profile = $associate->profile;
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('billing.invoices.show', $invoice) }}"
                wire:navigate
                class="text-sm font-semibold text-gw-accent-dark hover:underline"
            >
                ← Back to invoice
            </a>
        </div>

        @if ($invoice->isPayable())
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="bg-gw-primary px-6 py-5 text-white">
                        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Secure checkout</p>
                        <h1 class="mt-2 text-2xl font-semibold">Pay invoice {{ $invoice->number }}</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-200">
                            Card details are used only to complete this charge. GWTracks stores the card brand and last four digits — never the full number or security code.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('billing.invoices.pay.store', $invoice) }}" class="space-y-5 px-6 py-6" autocomplete="on">
                        @csrf

                        <div>
                            <label for="cardholder_name" class="text-sm font-semibold text-gw-primary">Name on card</label>
                            <input
                                id="cardholder_name"
                                name="cardholder_name"
                                type="text"
                                value="{{ old('cardholder_name', $associate->name) }}"
                                required
                                maxlength="120"
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            <x-input-error :messages="$errors->get('cardholder_name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="card_number" class="text-sm font-semibold text-gw-primary">Card number</label>
                            <input
                                id="card_number"
                                name="card_number"
                                type="text"
                                inputmode="numeric"
                                autocomplete="cc-number"
                                placeholder="ACCT-000015"
                                value="{{ old('card_number') }}"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 font-mono text-sm tracking-wide shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            <x-input-error :messages="$errors->get('card_number')" class="mt-1" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="card_expiry" class="text-sm font-semibold text-gw-primary">Expiration</label>
                                <input
                                    id="card_expiry"
                                    name="card_expiry"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="cc-exp"
                                    placeholder="MM/YY"
                                    value="{{ old('card_expiry') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                                <x-input-error :messages="$errors->get('card_expiry')" class="mt-1" />
                            </div>
                            <div>
                                <label for="card_cvc" class="text-sm font-semibold text-gw-primary">Security code</label>
                                <input
                                    id="card_cvc"
                                    name="card_cvc"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="cc-csc"
                                    placeholder="123"
                                    required
                                    class="mt-1 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                                >
                                <x-input-error :messages="$errors->get('card_cvc')" class="mt-1" />
                            </div>
                        </div>

                        @error('invoice')
                            <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror

                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <input
                                type="checkbox"
                                name="enable_auto_pay"
                                value="1"
                                @checked(old('enable_auto_pay', $enableAutoPayDefault ?? false))
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-gw-accent focus:ring-gw-accent"
                            >
                            <span>
                                <span class="block text-sm font-semibold text-gw-primary">
                                    {{ $invoice->isAnnual() ? 'Automatically renew each year' : 'Turn on monthly auto-pay' }}
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-slate-600">
                                    Save this card’s brand and last four digits to mark future invoices paid automatically. The full card number is not stored.
                                </span>
                            </span>
                        </label>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                            <p class="text-sm text-slate-600">Charged in {{ $invoice->currency }}.</p>
                            <button
                                type="submit"
                                class="rounded-md bg-gw-accent px-5 py-2.5 text-sm font-extrabold uppercase tracking-wide text-gw-primary hover:bg-gw-accent-hover"
                            >
                                Pay {{ $invoice->amountLabel() }}
                            </button>
                        </div>
                    </form>
                </section>

                <aside class="space-y-4">
                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount due</p>
                        <p class="mt-2 text-3xl font-semibold text-gw-primary">{{ $invoice->amountLabel() }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $invoice->description }}</p>
                    </section>

                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Billed to</p>
                        <p class="mt-2 font-semibold text-gw-primary">{{ $associate->name }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $associate->email }}</p>
                        @if ($profile?->gw_associate_id)
                            <p class="mt-1 text-sm text-slate-600">GW Associate ID {{ $profile->gw_associate_id }}</p>
                        @endif
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Period</p>
                        <p class="mt-2 text-sm font-semibold text-gw-primary">{{ $invoice->periodLabel() }}</p>
                        <p class="mt-1 text-sm text-slate-600">Rank at billing: {{ $invoice->rank_label }}</p>
                    </section>
                </aside>
            </div>
        @else
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-lg font-semibold text-gw-primary">This invoice is not open for payment</p>
                <p class="mx-auto mt-2 max-w-lg text-sm text-slate-600">
                    Status: {{ $invoice->statusLabel() }}.
                    @if ($invoice->isPaid())
                        Paid {{ $invoice->paid_at?->format('M j, Y') }}.
                    @endif
                </p>
                <a href="{{ route('billing.invoices.show', $invoice) }}" class="mt-4 inline-block text-sm font-semibold text-gw-accent-dark hover:underline">
                    View invoice
                </a>
            </section>
        @endif
    </div>
</x-app-layout>
