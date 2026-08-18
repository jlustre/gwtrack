@php
    $isOwnProfile = $isOwnProfile ?? true;
    $useLivewire = $useLivewire ?? false;
    $formAction = $isOwnProfile
        ? route('profile.production.store')
        : route('team.member.production.store', $user);
    $productionFeedbackValue = $useLivewire
        ? ($productionFeedback ?? null)
        : session('production_feedback');
@endphp

@if ($productionFeedbackValue)
    <div
        id="member-production-feedback"
        class="mb-4 rounded-lg border px-4 py-3 text-sm {{ ($productionFeedbackValue['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}"
        role="alert"
    >
        <p class="font-semibold">{{ ($productionFeedbackValue['type'] ?? '') === 'success' ? 'Production saved' : 'Could not save production' }}</p>
        <p class="mt-1">{{ $productionFeedbackValue['message'] }}</p>
    </div>
@endif

@if ($errors->any() && collect($errors->keys())->intersect([
    'description', 'policy_reference', 'annual_premium', 'posted_at',
    'productionDescription', 'productionPolicyReference', 'productionAnnualPremium', 'productionPostedAt',
])->isNotEmpty())
    <div id="member-production-feedback" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-semibold">Could not save production</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    @if ($useLivewire)
        wire:submit="storeProduction"
    @else
        method="POST"
        action="{{ $formAction }}"
    @endif
    class="mb-6 rounded-lg border border-gw-accent/30 bg-gw-accent-soft/40 p-4"
>
    @unless ($useLivewire)
        @csrf
    @endunless

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-gw-primary">
                @if ($isOwnProfile)
                    Record production
                @else
                    Record production for {{ $user->name }}
                @endif
            </h3>
            <p class="mt-1 text-xs text-slate-600">
                @if ($isOwnProfile)
                    Add a new annual premium entry to your production history.
                @else
                    Add a new annual premium entry on behalf of this member.
                @endif
            </p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="production_description" class="block text-xs font-semibold uppercase text-slate-500">Description</label>
            <input
                id="production_description"
                @if ($useLivewire) wire:model="productionDescription" @else name="description" @endif
                type="text"
                value="{{ $useLivewire ? '' : old('description') }}"
                required
                maxlength="255"
                placeholder="Policy or case description"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:outline-none focus:ring-1 focus:ring-gw-accent"
            />
        </div>

        <div>
            <label for="production_policy_reference" class="block text-xs font-semibold uppercase text-slate-500">Policy reference</label>
            <input
                id="production_policy_reference"
                @if ($useLivewire) wire:model="productionPolicyReference" @else name="policy_reference" @endif
                type="text"
                value="{{ $useLivewire ? '' : old('policy_reference') }}"
                maxlength="255"
                placeholder="Optional"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:outline-none focus:ring-1 focus:ring-gw-accent"
            />
        </div>

        <div>
            <label for="production_annual_premium" class="block text-xs font-semibold uppercase text-slate-500">Annual premium</label>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">$</span>
                <input
                    id="production_annual_premium"
                    @if ($useLivewire) wire:model="productionAnnualPremium" @else name="annual_premium" @endif
                    type="number"
                    step="0.01"
                    min="0.01"
                    value="{{ $useLivewire ? '' : old('annual_premium') }}"
                    required
                    placeholder="0.00"
                    class="block w-full rounded-lg border border-slate-300 py-2 pl-7 pr-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:outline-none focus:ring-1 focus:ring-gw-accent"
                />
            </div>
        </div>

        <div>
            <label for="production_posted_at" class="block text-xs font-semibold uppercase text-slate-500">Posted date</label>
            <input
                id="production_posted_at"
                @if ($useLivewire) wire:model="productionPostedAt" @else name="posted_at" @endif
                type="date"
                value="{{ $useLivewire ? $productionPostedAt : old('posted_at', now()->toDateString()) }}"
                max="{{ now()->toDateString() }}"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:outline-none focus:ring-1 focus:ring-gw-accent"
            />
        </div>
    </div>

    <div class="mt-4 flex justify-end">
        <button
            type="submit"
            class="inline-flex items-center rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary transition hover:bg-gw-accent-hover"
            @if ($useLivewire)
                wire:loading.attr="disabled"
                wire:target="storeProduction"
            @endif
        >
            @if ($useLivewire)
                <span wire:loading.remove wire:target="storeProduction">Add production entry</span>
                <span wire:loading wire:target="storeProduction">Saving…</span>
            @else
                Add production entry
            @endif
        </button>
    </div>
</form>
