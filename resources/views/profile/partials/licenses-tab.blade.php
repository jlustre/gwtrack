@php
    $isOwnProfile = $isOwnProfile ?? true;
    $useLivewire = $useLivewire ?? false;
    $locationOptions = $profileContext['locationOptions'];
    $selectedLicenses = $useLivewire
        ? $insuranceLicenses
        : old('insurance_licenses', $user->profile?->insurance_licenses ?? []);
    $isLicensedLifeInsuranceValue = $useLivewire
        ? $isLicensedLifeInsurance
        : old(
            'is_licensed_life_insurance',
            $user->profile?->is_licensed_life_insurance ?? ($user->profile?->insurance_licenses ?? []) !== []
        );
    $licenseLabels = \App\Support\LocationOptions::labelsForJurisdictionKeys(
        is_array($selectedLicenses) ? $selectedLicenses : []
    );
    $licensesFeedbackValue = $useLivewire
        ? ($licensesFeedback ?? null)
        : session('licenses_feedback');
@endphp

<section>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Insurance Licenses</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">
                @if ($isOwnProfile)
                    Tell us whether you hold a life insurance license and, if so, which jurisdictions apply.
                @else
                    Life insurance licensing details for {{ $user->name }}.
                @endif
            </p>
        </div>
        @if ($isOwnProfile && $isLicensedLifeInsuranceValue && $licenseLabels !== [])
            <span class="rounded-full bg-gw-accent-soft px-3 py-1 text-xs font-semibold text-gw-accent-dark">
                {{ count($licenseLabels) }} {{ str('license')->plural(count($licenseLabels)) }}
            </span>
        @endif
    </div>

    @if ($licensesFeedbackValue)
        <div
            id="member-licenses-feedback"
            class="mb-5 rounded-lg border px-4 py-3 text-sm {{ $licensesFeedbackValue['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}"
            role="alert"
        >
            <p class="font-semibold {{ $licensesFeedbackValue['type'] === 'success' ? 'text-emerald-900' : 'text-red-900' }}">
                {{ $licensesFeedbackValue['type'] === 'success' ? 'Licenses saved' : 'Could not save licenses' }}
            </p>
            <p class="mt-1">{{ $licensesFeedbackValue['message'] }}</p>
        </div>
    @elseif ($errors->has('insurance_licenses') || $errors->has('insurance_licenses.*') || $errors->has('is_licensed_life_insurance') || $errors->has('insuranceLicenses') || $errors->has('insuranceLicenses.*') || $errors->has('isLicensedLifeInsurance'))
        <div id="member-licenses-feedback" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-semibold text-red-900">Could not save licenses</p>
            <ul class="mt-2 list-disc list-inside space-y-1">
                @foreach ($errors->get('is_licensed_life_insurance') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('isLicensedLifeInsurance') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('insurance_licenses') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('insuranceLicenses') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('insurance_licenses.*') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('insuranceLicenses.*') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isOwnProfile)
        <form
            @if ($useLivewire)
                wire:submit="saveLicenses"
                x-data="{ licensedLifeInsurance: @entangle('isLicensedLifeInsurance') }"
            @else
                method="post"
                action="{{ route('profile.licenses.update') }}"
                x-data="{ licensedLifeInsurance: @js((bool) $isLicensedLifeInsuranceValue) }"
                @submit="licensesSaving = true"
            @endif
            class="space-y-6"
        >
            @unless ($useLivewire)
                @csrf
                @method('patch')
            @endunless

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        @if ($useLivewire)
                            wire:model.live="isLicensedLifeInsurance"
                        @else
                            name="is_licensed_life_insurance"
                            value="1"
                            x-model="licensedLifeInsurance"
                            @checked($isLicensedLifeInsuranceValue)
                        @endif
                        class="mt-1 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                    >
                    <span>
                        <span class="text-sm font-semibold text-gw-primary">I am licensed in Life Insurance</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            Check this if you hold an active life insurance license in one or more jurisdictions.
                        </span>
                    </span>
                </label>
                @error($useLivewire ? 'isLicensedLifeInsurance' : 'is_licensed_life_insurance')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="licensedLifeInsurance" x-cloak>
                @include('cfm.partials.licensed-jurisdictions-picker', [
                    'locationOptions' => $locationOptions,
                    'selectedKeys' => is_array($selectedLicenses) ? $selectedLicenses : [],
                    'inputName' => 'insurance_licenses',
                    'wireModel' => $useLivewire ? 'insuranceLicenses' : null,
                    'title' => 'License jurisdictions',
                    'description' => 'Expand each country to select the states or provinces where you are licensed. You can choose multiple regions.',
                    'sectionClass' => 'rounded-lg border border-slate-200 bg-slate-50/80 p-4',
                    'labelClass' => 'text-sm font-semibold text-gw-primary',
                    'collapsibleCountries' => true,
                ])
            </div>

            @if ($isLicensedLifeInsuranceValue && $licenseLabels !== [])
                <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft/60 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Currently saved</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($licenseLabels as $label)
                            <span class="rounded-full border border-gw-accent/40 bg-white px-3 py-1 text-xs font-semibold text-gw-primary">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    @if ($useLivewire)
                        wire:loading.attr="disabled"
                        wire:target="saveLicenses"
                    @else
                        :disabled="licensesSaving"
                    @endif
                >
                    @if ($useLivewire)
                        <span wire:loading.remove wire:target="saveLicenses">Save Licenses</span>
                        <span wire:loading wire:target="saveLicenses">Saving…</span>
                    @else
                        <span x-show="! licensesSaving">Save Licenses</span>
                        <span x-show="licensesSaving" x-cloak>Saving…</span>
                    @endif
                </button>
            </div>
        </form>
    @else
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Licensed in Life Insurance</p>
            <p class="mt-1 text-sm font-semibold text-gw-primary">
                {{ ($user->profile?->is_licensed_life_insurance ?? false) ? 'Yes' : 'No' }}
            </p>
        </div>

        @if ($user->profile?->is_licensed_life_insurance && $licenseLabels !== [])
            <div class="mt-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">License jurisdictions</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($licenseLabels as $label)
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-gw-primary shadow-sm">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        @elseif ($user->profile?->is_licensed_life_insurance)
            <p class="mt-4 text-sm text-slate-600">No jurisdictions recorded yet.</p>
        @elseif ($licenseLabels === [])
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
                No life insurance license recorded for this member.
            </div>
        @endif
    @endif
</section>
