@php
    $locationOptions = $locationOptions ?? LocationOptions::forPortal();
    $profileCompletionForm = $profileCompletionForm ?? [];
    $currentContactTime = $profileCompletionForm['best_contact_time'] ?? old('best_contact_time', $user->profile?->best_contact_time ?? '');
    $contactTimeIsLegacy = filled($currentContactTime) && ! array_key_exists($currentContactTime, $locationOptions['contactTimes']);
@endphp

<div
    x-show="profileCompletionOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="profile-completion-modal-title"
>
    <div
        class="absolute inset-0 bg-slate-950/65"
        x-on:click="dismissProfileCompletion()"
        aria-hidden="true"
    ></div>
    <div
        class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-2xl"
    >
        <div class="shrink-0 border-b border-slate-200 bg-gradient-to-r from-gw-primary to-gw-primary-hover px-6 py-5 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Welcome to GWTracks</p>
                    <h2 id="profile-completion-modal-title" class="mt-1 text-2xl font-semibold">Complete your profile</h2>
                    <p class="mt-2 text-sm text-slate-200">Finish your member details so your team can support your onboarding.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-white/20 p-2 text-slate-200 transition hover:border-gw-accent hover:bg-white/10 hover:text-white"
                    x-on:click="dismissProfileCompletion()"
                    aria-label="Close profile completion form"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-5 rounded-lg border border-white/10 bg-white/10 p-4">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Profile completion</p>
                        <p class="mt-1 text-3xl font-bold" x-text="`${completionPercent}%`"></p>
                    </div>
                    <p class="text-right text-xs text-slate-300">
                        <span x-text="completedFieldCount"></span> of <span x-text="completionFields.length"></span> fields complete
                    </p>
                </div>
                <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/15">
                    <div class="h-3 rounded-full bg-gw-accent transition-all duration-300" :style="`width: ${completionPercent}%`"></div>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col sm:flex-row">
            <div class="min-h-0 min-w-0 flex-1 overflow-y-auto p-6">
            <form id="profile-completion-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5" @submit="profileSaving = true">
                @csrf
                @method('patch')
                <input type="hidden" name="redirect_to" value="dashboard">

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold text-red-900">Please fix the following</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('profile_feedback') && (session('profile_feedback')['type'] ?? '') === 'success')
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                        {{ session('profile_feedback')['message'] }}
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="completion_name" :value="__('Name')" />
                        <x-text-input id="completion_name" name="name" type="text" class="mt-1 block w-full" x-model="form.name" required autocomplete="name" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="completion_email" :value="__('Email')" />
                        <x-text-input id="completion_email" name="email" type="email" class="mt-1 block w-full" x-model="form.email" required autocomplete="username" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="completion_phone" :value="__('Phone')" />
                        <x-text-input id="completion_phone" name="phone" type="text" class="mt-1 block w-full" x-model="form.phone" autocomplete="tel" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="completion_best_contact_time" value="Best Contact Time" />
                        <select id="completion_best_contact_time" name="best_contact_time" x-model="form.best_contact_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Select best time to reach you</option>
                            @if ($contactTimeIsLegacy)
                                <option value="{{ $currentContactTime }}" selected>{{ $currentContactTime }}</option>
                            @endif
                            @foreach ($locationOptions['contactTimes'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('best_contact_time')" />
                    </div>

                    <div>
                        <x-input-label for="completion_gw_associate_id" :value="__('GW Associate ID')" />
                        <x-text-input id="completion_gw_associate_id" name="gw_associate_id" type="text" class="mt-1 block w-full" x-model="form.gw_associate_id" placeholder="GW-1001" />
                        <x-input-error class="mt-2" :messages="$errors->get('gw_associate_id')" />
                    </div>

                    <div>
                        <x-input-label for="completion_gw_invite_link" :value="__('Greatway invite URL')" />
                        <x-text-input id="completion_gw_invite_link" name="gw_invite_link" type="text" class="mt-1 block w-full" x-model="form.gw_invite_link" placeholder="https://greatwayfinancial.com/invite/..." inputmode="url" autocomplete="url" />
                        <x-input-error class="mt-2" :messages="$errors->get('gw_invite_link')" />
                    </div>

                    <div>
                        <x-input-label for="completion_city" :value="__('City')" />
                        <x-text-input id="completion_city" name="city" type="text" class="mt-1 block w-full" x-model="form.city" />
                        <x-input-error class="mt-2" :messages="$errors->get('city')" />
                    </div>

                    @include('partials.profile-location-selects', [
                        'locationOptions' => $locationOptions,
                        'countryId' => $profileCompletionForm['country_id'] ?? $user->profile?->country_id,
                        'stateProvinceId' => $profileCompletionForm['state_province_id'] ?? $user->profile?->state_province_id,
                        'timezoneId' => $profileCompletionForm['timezone_id'] ?? $user->profile?->timezone_id,
                        'countryModel' => 'form.country_id',
                        'provinceModel' => 'form.state_province_id',
                        'timezoneModel' => 'form.timezone_id',
                        'provinceOptionsGetter' => 'completionProvinceOptions',
                        'countryChangeHandler' => 'onCompletionCountryChange()',
                        'countryInputId' => 'completion_country_id',
                        'provinceInputId' => 'completion_state_province_id',
                        'timezoneInputId' => 'completion_timezone_id',
                    ])
                </div>

                <div>
                    <x-input-label for="completion_bio" :value="__('Member Bio')" />
                    <textarea
                        id="completion_bio"
                        name="bio"
                        rows="4"
                        x-model="form.bio"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        placeholder="Share your goals, specialty, or mentorship focus."
                    ></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('bio')" />
                </div>

                @include('profile.partials.profile-completion-photo-field', [
                    'user' => $user,
                    'photoPreviewUrl' => $profileCompletionPhotoPreview ?? $user->profilePhotoUrl(),
                    'hasStagedProfilePhoto' => $hasStagedProfilePhoto ?? false,
                ])

            </form>
            </div>

            <aside class="shrink-0 overflow-y-auto border-t border-slate-200 bg-slate-50 p-4 sm:w-56 sm:border-l sm:border-t-0 md:w-60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Required fields</p>
                <p class="mt-1 text-xs text-slate-500">Checked automatically when a field has a value.</p>
                <div class="mt-4 space-y-2.5">
                    <template x-for="field in completionFields" :key="field.key">
                        <label class="flex items-start gap-2.5 text-sm text-gw-primary">
                            <input
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-gw-accent focus:ring-gw-accent disabled:opacity-100"
                                :checked="field.filled"
                                disabled
                                readonly
                                tabindex="-1"
                                :aria-label="`${field.label} ${field.filled ? 'complete' : 'incomplete'}`"
                            >
                            <span x-text="field.label" :class="field.filled ? 'text-slate-700' : 'text-slate-500'"></span>
                        </label>
                    </template>
                </div>
            </aside>
        </div>

        <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    form="profile-completion-form"
                    class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="profileSaving"
                >
                    <span x-show="! profileSaving">Save profile</span>
                    <span x-show="profileSaving" x-cloak>Saving…</span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-50"
                    x-on:click="dismissProfileCompletion()"
                >
                    Continue later
                </button>
            </div>
        </div>
    </div>
</div>
