@php
    $isOwnProfile = $isOwnProfile ?? true;
    $useLivewire = $useLivewire ?? false;
    $hasNonGwValidationErrors = $errors->any() && collect($errors->keys())->contains(
        fn (string $key): bool => ! in_array($key, ['gw_associate_id', 'gw_invite_link', 'gwAssociateId', 'gwInviteLink'], true)
            && ! str_starts_with($key, 'insurance_licenses')
            && ! str_starts_with($key, 'insuranceLicenses')
    );
    $hasLicenseValidationErrors = $errors->has('insurance_licenses')
        || $errors->has('insurance_licenses.*')
        || $errors->has('insuranceLicenses')
        || $errors->has('insuranceLicenses.*');
    $tabs = [
        'profile' => 'Profile Details',
        'licenses' => 'Licenses',
        'checklists' => 'Checklists',
        'recruits' => 'Recruits',
        'annual-premium' => 'Annual Premium',
    ];
    $initialTab = $useLivewire ? ($tab ?? 'profile') : request('tab', 'profile');
@endphp

<section
    x-data="{
        activeTab: @if ($useLivewire) @entangle('tab') @else @js($initialTab) @endif,
        profileSaving: false,
        licensesSaving: false,
        editCountryId: @js((string) ($useLivewire ? ($countryId ?? '') : old('country_id', $user->profile?->country_id ?? ''))),
        editProvinceId: @js((string) ($useLivewire ? ($stateProvinceId ?? '') : old('state_province_id', $user->profile?->state_province_id ?? ''))),
        editProvinces: @js($profileContext['locationOptions']['provincesByCountryId']),
        get editProvinceOptions() {
            return this.editProvinces[String(this.editCountryId)] || {};
        },
        syncProvinceSelect(selectId = 'state_province_id') {
            if (! window.rebuildProvinceSelectOptions) {
                return;
            }
            const next = window.rebuildProvinceSelectOptions(selectId, this.editProvinceOptions, this.editProvinceId);
            if (next !== this.editProvinceId) {
                this.editProvinceId = next;
            }
        },
        onCountryChange() {
            this.syncProvinceSelect('state_province_id');
        },
        submitProfileForm() {
            this.profileSaving = true;
        },
        setActiveTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            if (tab === 'profile') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            history.replaceState({}, '', url);
        },
    }"
    x-init="
        $nextTick(() => syncProvinceSelect('state_province_id'));
        @if ($useLivewire)
            @if ($licensesFeedback || $hasLicenseValidationErrors)
                activeTab = 'licenses';
                $nextTick(() => document.getElementById('member-licenses-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @elseif ($productionFeedback || collect($errors->keys())->intersect(['productionDescription', 'productionPolicyReference', 'productionAnnualPremium', 'productionPostedAt'])->isNotEmpty())
                activeTab = 'annual-premium';
                $nextTick(() => document.getElementById('member-production-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @elseif ($profileFeedback || $hasNonGwValidationErrors)
                activeTab = 'profile';
                $nextTick(() => document.getElementById('member-profile-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @endif
        @else
            @if (session('licenses_feedback') || $hasLicenseValidationErrors)
                activeTab = 'licenses';
                $nextTick(() => document.getElementById('member-licenses-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @elseif (session('production_feedback') || collect($errors->keys())->intersect(['description', 'policy_reference', 'annual_premium', 'posted_at'])->isNotEmpty())
                activeTab = 'annual-premium';
                $nextTick(() => document.getElementById('member-production-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @elseif (session('profile_feedback') || $hasNonGwValidationErrors)
                activeTab = 'profile';
                $nextTick(() => document.getElementById('member-profile-feedback')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            @endif
        @endif
    "
    class="rounded-lg border border-slate-200 bg-white shadow-sm"
>
    <div class="border-b border-slate-200 bg-slate-50/80 px-4 pt-4">
        <nav class="-mb-px flex flex-wrap gap-2" aria-label="Member profile sections">
            @foreach ($tabs as $key => $label)
                <button
                    type="button"
                    @click="setActiveTab(@js($key))"
                    :class="activeTab === @js($key)
                        ? 'border-gw-accent bg-white text-gw-primary shadow-sm'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-gw-primary'"
                    class="rounded-t-lg border px-4 py-2.5 text-sm font-semibold transition"
                >
                    {{ $label }}
                    @if ($key === 'annual-premium')
                        <span class="ml-1 rounded-full bg-gw-accent/20 px-2 py-0.5 text-xs text-gw-accent-dark">${{ number_format($memberTabs['annualPremiumTotal']) }}</span>
                    @elseif ($key === 'licenses' && ($user->profile?->is_licensed_life_insurance ?? false))
                        <span class="ml-1 rounded-full bg-gw-accent/20 px-2 py-0.5 text-xs text-gw-accent-dark">{{ count($user->profile->insurance_licenses ?? []) }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    <div class="p-6">
        <div x-show="activeTab === 'profile'">
            @include('profile.partials.profile-feedback')
            @include('profile.partials.update-profile-information-form')

            @if ($isOwnProfile)
            <div class="mt-8 grid gap-8 border-t border-slate-200 pt-8 lg:grid-cols-2">
                @include('profile.partials.update-profile-photo-form')
                @include('profile.partials.update-password-form')
            </div>
            @endif
        </div>

        <div x-show="activeTab === 'licenses'" x-cloak>
            @include('profile.partials.licenses-tab', [
                'user' => $user,
                'isOwnProfile' => $isOwnProfile,
                'profileContext' => $profileContext,
                'useLivewire' => $useLivewire,
            ])
        </div>

        <div x-show="activeTab === 'checklists'" x-cloak>
            @include('profile.partials.checklists-tab', [
                'user' => $user,
                'memberTabs' => $memberTabs,
                'isOwnProfile' => $isOwnProfile,
            ])
        </div>

        <div x-show="activeTab === 'recruits'" x-cloak>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Recruits</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Your downline by sponsorship level. Level 1 members are your direct recruits.
                    </p>
                    <p class="mt-2 text-sm font-semibold text-gw-primary">
                        Total recruits: {{ number_format($memberTabs['recruitsTotal']) }}
                        @if ($memberTabs['recruitsDirectTotal'] > 0)
                            <span class="font-normal text-slate-600">({{ number_format($memberTabs['recruitsDirectTotal']) }} at Level 1)</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('team.tree') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">View Genealogy →</a>
            </div>
            @include('profile.partials.tab-table-filterable', [
                'tableKey' => 'recruits',
                'columns' => [
                    ['key' => 'level_label', 'label' => 'Level'],
                    ['key' => 'member', 'label' => 'Member', 'type' => 'member'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'role'],
                    ['key' => 'rank', 'label' => 'Rank'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'sponsor', 'label' => 'Sponsor'],
                    ['key' => 'cfm', 'label' => 'CFM'],
                    ['key' => 'location', 'label' => 'Location', 'type' => 'location'],
                    ['key' => 'joined_at', 'label' => 'Joined'],
                    ['key' => 'onboarding', 'label' => 'Onboarding'],
                    ['key' => 'fap', 'label' => 'FAP'],
                ],
                'rows' => $memberTabs['recruits'],
                'searchKeys' => ['level_label', 'name', 'email', 'phone', 'role', 'rank', 'status', 'sponsor', 'cfm', 'province', 'country', 'country_flag', 'joined_at', 'onboarding', 'fap'],
                'searchPlaceholder' => 'Search by name, email, phone, role, rank, sponsor…',
                'filterFields' => [
                    [
                        'key' => 'level_label',
                        'label' => 'Level',
                        'allLabel' => 'All levels',
                        'dynamic' => true,
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'allLabel' => 'All statuses',
                        'options' => [
                            ['value' => '', 'label' => 'All statuses'],
                            ['value' => 'Active', 'label' => 'Active'],
                            ['value' => 'Inactive', 'label' => 'Inactive'],
                        ],
                    ],
                    ['key' => 'rank', 'label' => 'Rank', 'allLabel' => 'All ranks', 'dynamic' => true],
                    ['key' => 'role', 'label' => 'Role', 'allLabel' => 'All roles', 'dynamic' => true],
                    ['key' => 'province', 'label' => 'Province/State', 'allLabel' => 'All provinces', 'dynamic' => true],
                    ['key' => 'country', 'label' => 'Country', 'allLabel' => 'All countries', 'dynamic' => true],
                    ['key' => 'cfm', 'label' => 'CFM', 'allLabel' => 'All CFMs', 'dynamic' => true],
                ],
                'empty' => 'You have not recruited any team members yet.',
                'emptyFiltered' => 'No recruits match your search or filters.',
            ])
        </div>

        <div x-show="activeTab === 'annual-premium'" x-cloak>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Annual Premium</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if ($isOwnProfile)
                            Current-year production attributed to your milestones, team growth, and recorded entries.
                        @else
                            Current-year production for this member, including milestones and recorded entries.
                        @endif
                    </p>
                </div>
                <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary">
                    YTD Total: ${{ number_format($memberTabs['annualPremiumTotal']) }}
                </div>
            </div>

            @if ($canEnterProduction ?? false)
                @include('profile.partials.production-entry-form', [
                    'user' => $user,
                    'isOwnProfile' => $isOwnProfile,
                    'useLivewire' => $useLivewire,
                ])
            @endif

            @include('profile.partials.tab-table-filterable', [
                'tableKey' => 'annual-premium',
                'columns' => [
                    ['key' => 'period', 'label' => 'Period'],
                    ['key' => 'source', 'label' => 'Source'],
                    ['key' => 'description', 'label' => 'Description'],
                    ['key' => 'annual_premium', 'label' => 'Annual Premium'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'posted_at', 'label' => 'Posted'],
                ],
                'rows' => $memberTabs['annualPremium'],
                'searchKeys' => ['period', 'source', 'description', 'annual_premium', 'status', 'posted_at'],
                'searchPlaceholder' => 'Search by source, description, period…',
                'filterFields' => [
                    ['key' => 'period', 'label' => 'Period', 'allLabel' => 'All periods', 'dynamic' => true],
                    [
                        'key' => 'source',
                        'label' => 'Source',
                        'allLabel' => 'All sources',
                        'options' => [
                            ['value' => '', 'label' => 'All sources'],
                            ['value' => 'Onboarding', 'label' => 'Onboarding'],
                            ['value' => 'Field Apprenticeship', 'label' => 'Field Apprenticeship'],
                            ['value' => 'CFM Training', 'label' => 'CFM Training'],
                            ['value' => 'Direct Recruit', 'label' => 'Direct Recruit'],
                            ['value' => 'Manual Entry', 'label' => 'Manual Entry'],
                        ],
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'allLabel' => 'All statuses',
                        'options' => [
                            ['value' => '', 'label' => 'All statuses'],
                            ['value' => 'Posted', 'label' => 'Posted'],
                        ],
                    ],
                ],
                'sumKey' => 'premium_amount',
                'sumLabel' => 'Filtered total',
                'empty' => 'No annual premium production recorded yet.',
                'emptyFiltered' => 'No premium entries match your search or filters.',
            ])
        </div>
    </div>
</section>
