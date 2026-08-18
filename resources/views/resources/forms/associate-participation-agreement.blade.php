@php
    $field = fn (string $key) => old($key, $formData[$key] ?? '');
@endphp

<x-app-layout>
    <section class="mx-auto max-w-4xl space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Forms</p>
                <h1 class="mt-2 text-2xl font-semibold">Associate Participation Agreement</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                    Review the agreement below and complete the form. Your name, email, phone, associate ID, location, and sponsor are pre-filled from your account profile.
                </p>
            </div>

            @if (session('profile_feedback'))
                <div class="mx-6 mt-6 rounded-md border px-4 py-3 text-sm font-medium {{
                    session('profile_feedback.type') === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : 'border-red-200 bg-red-50 text-red-700'
                }}">
                    {{ session('profile_feedback.message') }}
                </div>
            @endif

            @if ($isSubmitted)
                <div class="mx-6 mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    You submitted this agreement on {{ $agreement->associate_signed_at?->format('M j, Y') }}.
                    <a href="{{ route('resources.forms.associate-participation-agreement.download') }}" class="ml-1 font-semibold underline">Download signed PDF</a>
                </div>
            @endif

            <div class="space-y-8 px-6 py-6">
                @include('resources.forms.partials.associate-participation-agreement-body')

                <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft px-4 py-3 text-sm text-gw-accent-dark">
                    Profile-linked fields below are loaded from your user account and profile. Update your
                    <a href="{{ route('profile.edit') }}" class="font-semibold underline">profile</a>
                    if any information is incorrect before signing.
                </div>

                <form method="POST" action="{{ route('resources.forms.associate-participation-agreement.store') }}" class="space-y-6 rounded-lg border border-slate-200 bg-slate-50 p-6">
                    @csrf

                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Associate Information</h2>
                        <p class="mt-1 text-sm text-slate-600">Pre-filled from your user and profile records.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="effective_date" class="block text-sm font-semibold text-gw-primary">Effective Date</label>
                            <input
                                id="effective_date"
                                name="effective_date"
                                type="date"
                                value="{{ $field('effective_date') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('effective_date')" class="mt-1" />
                        </div>
                        <div>
                            <label for="full_name" class="block text-sm font-semibold text-gw-primary">Full Name</label>
                            <input
                                id="full_name"
                                name="full_name"
                                type="text"
                                value="{{ $field('full_name') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gw-primary">Email Address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ $field('email') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gw-primary">Phone Number</label>
                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                value="{{ $field('phone') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div>
                            <label for="associate_id" class="block text-sm font-semibold text-gw-primary">Associate ID</label>
                            <input
                                id="associate_id"
                                name="associate_id"
                                type="text"
                                value="{{ $field('associate_id') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('associate_id')" class="mt-1" />
                        </div>
                        <div>
                            <label for="sponsor_name" class="block text-sm font-semibold text-gw-primary">Sponsor Name</label>
                            <input
                                id="sponsor_name"
                                name="sponsor_name"
                                type="text"
                                value="{{ $field('sponsor_name') }}"
                                readonly
                                class="mt-1 block w-full rounded-md border-slate-200 bg-slate-100 text-sm text-slate-700 shadow-sm"
                            >
                        </div>
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-semibold text-gw-primary">Address</label>
                            <input
                                id="address"
                                name="address"
                                type="text"
                                value="{{ $field('address') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-semibold text-gw-primary">City</label>
                            <input
                                id="city"
                                name="city"
                                type="text"
                                value="{{ $field('city') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('city')" class="mt-1" />
                        </div>
                        <div>
                            <label for="state_province" class="block text-sm font-semibold text-gw-primary">State / Province</label>
                            <input
                                id="state_province"
                                name="state_province"
                                type="text"
                                value="{{ $field('state_province') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('state_province')" class="mt-1" />
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-semibold text-gw-primary">Country</label>
                            <input
                                id="country"
                                name="country"
                                type="text"
                                value="{{ $field('country') }}"
                                @disabled($isSubmitted)
                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                            >
                            <x-input-error :messages="$errors->get('country')" class="mt-1" />
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-6">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Associate Signature</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="associate_signature" class="block text-sm font-semibold text-gw-primary">Typed Signature (full legal name)</label>
                                <input
                                    id="associate_signature"
                                    name="associate_signature"
                                    type="text"
                                    value="{{ $field('associate_signature') }}"
                                    @disabled($isSubmitted)
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                                >
                                <x-input-error :messages="$errors->get('associate_signature')" class="mt-1" />
                            </div>
                            <div>
                                <label for="associate_signed_at" class="block text-sm font-semibold text-gw-primary">Date Signed</label>
                                <input
                                    id="associate_signed_at"
                                    name="associate_signed_at"
                                    type="date"
                                    value="{{ $field('associate_signed_at') }}"
                                    @disabled($isSubmitted)
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent disabled:bg-slate-100"
                                >
                                <x-input-error :messages="$errors->get('associate_signed_at')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    @unless ($isSubmitted)
                        <div class="rounded-md border border-slate-200 bg-white p-4">
                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="acknowledgment_accepted"
                                    value="1"
                                    @checked(old('acknowledgment_accepted', $formData['acknowledgment_accepted'] ?? false))
                                    class="mt-1 rounded border-slate-300 text-gw-accent focus:ring-gw-accent"
                                >
                                <span class="text-sm leading-6 text-slate-700">
                                    I have read and understood this Associate Participation Agreement and agree to its terms.
                                </span>
                            </label>
                            <x-input-error :messages="$errors->get('acknowledgment_accepted')" class="mt-2" />
                        </div>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('resources.documents') }}" class="inline-flex justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Back to Documents</a>
                            <button type="submit" class="inline-flex justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                                Submit Agreement
                            </button>
                        </div>
                    @endunless
                </form>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sponsor Acknowledgment</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-3 text-sm">
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Sponsor Name</p><p class="mt-2 border-b border-slate-300 pb-2 text-slate-600">{{ $field('sponsor_name') ?: 'Pending sponsor signature' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Signature</p><p class="mt-2 border-b border-slate-300 pb-2 italic text-slate-400">Awaiting sponsor</p></div>
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="mt-2 border-b border-slate-300 pb-2 italic text-slate-400">—</p></div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Organization Approval</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-3 text-sm">
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Authorized Representative</p><p class="mt-2 border-b border-slate-300 pb-2 italic text-slate-400">Awaiting organization</p></div>
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Signature</p><p class="mt-2 border-b border-slate-300 pb-2 italic text-slate-400">—</p></div>
                        <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="mt-2 border-b border-slate-300 pb-2 italic text-slate-400">—</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
