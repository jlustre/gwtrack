@php
    $isOwnProfile = $isOwnProfile ?? true;
    $useLivewire = $useLivewire ?? false;
    $readonly = $profileContext['readonly'];
    $canViewSensitive = $profileContext['canViewSensitive'] ?? true;
    $locationOptions = $profileContext['locationOptions'];
    $contactTimes = $locationOptions['contactTimes'];
    $currentContactTime = $useLivewire
        ? ($bestContactTime ?? '')
        : old('best_contact_time', $user->profile?->best_contact_time ?? '');
    $contactTimeIsLegacy = filled($currentContactTime) && ! array_key_exists($currentContactTime, $contactTimes);
@endphp

<section>
    <p class="text-sm text-slate-600">
        @if ($isOwnProfile)
            Update your editable contact and licensing details. Team and login information below is managed by the system.
        @else
            Contact, licensing, and team information for this member. Editable fields are shown read-only.
        @endif
    </p>

    <dl class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Sponsor</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['sponsor'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Agency Owner</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['agencyOwner'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">CFM / Mentor</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['mentor'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Team</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['team'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Rank</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['rank'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Role</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['role'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Joined</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['joinedAt'] }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Last Login</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['lastLoginAt'] }}</dd>
        </div>
        @if ($canViewSensitive)
        <div>
            <dt class="text-xs font-semibold uppercase text-slate-500">Last IP</dt>
            <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $readonly['lastLoginIp'] }}</dd>
        </div>
        @endif
    </dl>

    @if (! $isOwnProfile)
        <dl class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Name</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Email</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Phone</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->phone ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Best Contact Time</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->best_contact_time ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">GW Associate ID</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->gw_associate_id ?? 'Not added' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-slate-500">Greatway Invite URL</dt>
                <dd class="mt-1 break-all text-sm font-semibold text-gw-primary">{{ $user->profile?->gw_invite_link ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">City</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->city ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Country</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->country ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Province / State</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->province ?? 'Not added' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Timezone</dt>
                <dd class="mt-1 text-sm font-semibold text-gw-primary">{{ $user->profile?->timezone ?? 'Not added' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-slate-500">Member Bio</dt>
                <dd class="mt-1 text-sm text-gw-primary">{{ $user->profile?->bio ?? 'Not added' }}</dd>
            </div>
        </dl>
    @else
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @php
        $inlineProfileFeedback = $useLivewire ? ($profileFeedback ?? null) : session('profile_feedback');
        $provinceOptions = $locationOptions['provincesByCountryId'][$useLivewire ? ($countryId ?? '') : old('country_id', $user->profile?->country_id ?? '')] ?? [];
    @endphp

    <form
        @if ($useLivewire)
            wire:submit="saveProfile"
        @else
            method="post"
            action="{{ route('profile.update') }}"
            @submit="submitProfileForm()"
        @endif
        class="mt-6 space-y-6"
    >
        @unless ($useLivewire)
            @csrf
            @method('patch')
        @endunless

        @if ($inlineProfileFeedback && ($inlineProfileFeedback['type'] ?? '') === 'error')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold text-red-900">Could not save profile</p>
                <p class="mt-1">{{ $inlineProfileFeedback['message'] }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold text-red-900">Please fix the following</p>
                <ul class="mt-2 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                @if ($useLivewire)
                    <x-text-input id="name" wire:model="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                @else
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                @endif
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                @if ($useLivewire)
                    <x-text-input id="email" wire:model="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                @else
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                @endif
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                @if ($useLivewire)
                    <x-text-input id="phone" wire:model="phone" type="text" class="mt-1 block w-full" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                @else
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->profile?->phone)" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                @endif
            </div>

            <div>
                <x-input-label for="best_contact_time" value="Best Contact Time" />
                <select
                    id="best_contact_time"
                    @if ($useLivewire) wire:model="bestContactTime" @else name="best_contact_time" @endif
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                    <option value="">Select best time to reach you</option>
                    @if ($contactTimeIsLegacy)
                        <option value="{{ $currentContactTime }}" selected>{{ $currentContactTime }}</option>
                    @endif
                    @foreach ($contactTimes as $value => $label)
                        <option value="{{ $value }}" @selected($currentContactTime === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get($useLivewire ? 'bestContactTime' : 'best_contact_time')" />
            </div>

            <div>
                <x-input-label for="city" :value="__('City')" />
                @if ($useLivewire)
                    <x-text-input id="city" wire:model="city" type="text" class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                @else
                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $user->profile?->city)" />
                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                @endif
            </div>

            @if ($useLivewire)
                <div>
                    <x-input-label for="country_id" :value="__('Country')" />
                    <select
                        id="country_id"
                        wire:model.live="countryId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="">Select country</option>
                        @foreach ($locationOptions['countries'] as $id => $countryName)
                            <option value="{{ $id }}" @selected((string) $countryId === (string) $id)>{{ $countryName }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('countryId')" />
                </div>

                <div>
                    <x-input-label for="state_province_id" :value="__('Province / State')" />
                    <select
                        id="state_province_id"
                        wire:model="stateProvinceId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="">Select province / state</option>
                        @foreach ($provinceOptions as $id => $provinceName)
                            <option value="{{ $id }}" @selected((string) $stateProvinceId === (string) $id)>{{ $provinceName }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('stateProvinceId')" />
                </div>

                <div>
                    <x-input-label for="timezone_id" :value="__('Timezone')" />
                    <select
                        id="timezone_id"
                        wire:model="timezoneId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                        <option value="">Select timezone</option>
                        @foreach ($locationOptions['timezones'] as $id => $label)
                            <option value="{{ $id }}" @selected((string) $timezoneId === (string) $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('timezoneId')" />
                </div>
            @else
                @include('partials.profile-location-selects', [
                    'locationOptions' => $locationOptions,
                    'countryId' => $user->profile?->country_id,
                    'stateProvinceId' => $user->profile?->state_province_id,
                    'timezoneId' => $user->profile?->timezone_id,
                ])
            @endif
        </div>

        <div>
            <x-input-label for="bio" :value="__('Member Bio')" />
            <textarea
                id="bio"
                @if ($useLivewire) wire:model="bio" @else name="bio" @endif
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                placeholder="Share your goals, specialty, or mentorship focus."
            >{{ $useLivewire ? $bio : old('bio', $user->profile?->bio) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="mt-2 text-sm text-gray-800">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-green-600">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            <button
                type="submit"
                class="inline-flex items-center rounded-md border border-transparent bg-gw-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gw-primary-hover focus:bg-gw-primary-hover focus:outline-none focus:ring-2 focus:ring-gw-accent focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                @if ($useLivewire)
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                @else
                    :disabled="profileSaving"
                @endif
            >
                @if ($useLivewire)
                    <span wire:loading.remove wire:target="saveProfile">Save Profile</span>
                    <span wire:loading wire:target="saveProfile">Saving…</span>
                @else
                    <span x-show="! profileSaving">Save Profile</span>
                    <span x-show="profileSaving" x-cloak>Saving…</span>
                @endif
            </button>
        </div>
    </form>
    @endif
</section>
