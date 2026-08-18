<x-app-layout>

    <div

        x-data="{

            profileCompletionOpen: @js(($forceProfileCompletionModal ?? false) || session('show_profile_completion_modal', false)),

            profileSaving: false,

            completionFields: @js($profileCompletion['fields'] ?? []),

            completionPercent: @js($profileCompletion['percent'] ?? 0),

            form: {

                name: @js($profileCompletionForm['name'] ?? $user->name),

                email: @js($profileCompletionForm['email'] ?? $user->email),

                phone: @js($profileCompletionForm['phone'] ?? ''),

                best_contact_time: @js($profileCompletionForm['best_contact_time'] ?? ''),

                gw_associate_id: @js($profileCompletionForm['gw_associate_id'] ?? ''),

                gw_invite_link: @js($profileCompletionForm['gw_invite_link'] ?? ''),

                city: @js($profileCompletionForm['city'] ?? ''),

                country_id: @js($profileCompletionForm['country_id'] ?? ''),

                state_province_id: @js($profileCompletionForm['state_province_id'] ?? ''),

                timezone_id: @js($profileCompletionForm['timezone_id'] ?? ''),

                bio: @js($profileCompletionForm['bio'] ?? ''),

            },

            locationProvinces: @js($locationOptions['provincesByCountryId'] ?? []),

            get completionProvinceOptions() {

                return this.locationProvinces[String(this.form.country_id)] || {};

            },

            syncCompletionProvinceSelect() {

                const next = window.rebuildProvinceSelectOptions(

                    'completion_state_province_id',

                    this.completionProvinceOptions,

                    this.form.state_province_id,

                );

                if (next !== this.form.state_province_id) {

                    this.form.state_province_id = next;

                }

            },

            onCompletionCountryChange() {

                this.syncCompletionProvinceSelect();

            },

            get completedFieldCount() {

                return this.completionFields.filter((field) => field.filled).length;

            },

            dismissProfileCompletion() {

                this.profileCompletionOpen = false;

            },

        }"

        x-init="$nextTick(() => syncCompletionProvinceSelect())"

    >

    <div class="space-y-6">

        @include('dashboard.partials.welcome-banner', ['home' => $home ?? [], 'user' => $user])



        @include('dashboard.partials.quick-actions', ['home' => $home ?? []])

        @include('dashboard.partials.progress-trackers', ['home' => $home ?? []])



        @include('dashboard.partials.quick-links-panel', ['home' => $home ?? []])



        @include('dashboard.partials.stat-card-themes')



        <div x-data="dashboardStats(@js(['detailsUrlTemplate' => route('dashboard.stat-details', ['type' => '__TYPE__'], absolute: false)]))">

        <section>

            <div class="mb-3">

                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Performance Statistics</h2>

                <p class="mt-1 text-sm text-slate-500">Team and personal metrics pulled from live tracker, CRM, and production data.</p>

            </div>



            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">My Team</h3>

            <div class="grid auto-rows-fr gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">

                @foreach ($statCards['team'] ?? [] as $card)

                    @include('dashboard.partials.stat-card-with-details', [

                        'card' => $card,

                        'theme' => dashboardTrackerStatTheme($card['key']),

                        'subtitle' => 'Team average completion',

                        'context' => 'team',

                    ])

                @endforeach

            </div>

        </section>



        @if (! empty($statCards['personal'] ?? []))

            <section class="mt-6">

                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">My Progress</h3>

                <div class="grid auto-rows-fr gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

                    @foreach ($statCards['personal'] as $card)

                        @php

                            $subtitle = match ($card['key']) {

                                'profile' => 'Complete your member profile',

                                'onboarding' => 'Onboarding checklist progress',

                                'credentials' => 'Licensing milestone progress',

                                'apprenticeship' => 'Field apprenticeship progress',

                                'training' => 'CFM training progress',

                                'prospects' => 'Active prospects in your CRM',

                                'hot_prospects' => 'High-priority leads',

                                'followups_due' => 'Due today or overdue',

                                'activities' => 'Prospecting activity (last 30 days)',

                                'prospect_conversion' => 'Prospect to client rate',

                                'recruits' => 'Members in your downline',

                                'production' => 'Annual production total',

                                'fna' => 'Approved FNA submissions',

                                default => null,

                            };

                        @endphp

                        @include('dashboard.partials.stat-card-with-details', [

                            'card' => $card,

                            'theme' => dashboardTrackerStatTheme($card['key']),

                            'subtitle' => $subtitle,

                            'context' => 'personal',

                            'url' => $card['url'] ?? null,

                            'showBar' => $card['show_bar'] ?? true,

                        ])

                    @endforeach

                </div>

            </section>

        @endif



        @include('dashboard.partials.stat-details-modal')

        </div>



        @include('dashboard.partials.activity-hub', ['activity' => $activity ?? []])

    </div>



    @include('dashboard.partials.profile-completion-modal', [

        'user' => $user,

        'locationOptions' => $locationOptions,

        'profileCompletionForm' => $profileCompletionForm,

        'profileCompletionPhotoPreview' => $profileCompletionPhotoPreview,

        'hasStagedProfilePhoto' => $hasStagedProfilePhoto ?? false,

    ])

    </div>

</x-app-layout>

