<x-guest-layout>
    @php
        $timezones = $locationOptions['timezones'];
        $currentCountryId = (string) old('country_id', '');
        $currentProvinceId = (string) old('state_province_id', '');
        $currentTimezoneId = (string) old('timezone_id', '');
        $provinceOptions = $locationOptions['provincesByCountryId'][$currentCountryId] ?? [];
        $provinceIsLegacy = filled($currentProvinceId)
            && ! array_key_exists($currentProvinceId, $provinceOptions);
    @endphp

    <div class="min-h-screen bg-[radial-gradient(circle_at_10%_20%,#111111,#000000)] px-4 py-8 text-slate-100 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl items-center justify-center">
            <div class="w-full overflow-hidden rounded-[2rem] border border-gw-accent/25 bg-black/80 shadow-[0_25px_45px_-12px_rgba(0,0,0,0.8),0_0_18px_rgba(132, 187, 65,0.22)]">
                <div class="grid bg-[#0b0b0c] lg:grid-cols-[0.95fr_1.1fr]">
                    <aside class="border-b border-gw-accent/25 bg-gradient-to-br from-black to-[#101012] p-6 sm:p-8 lg:border-b-0 lg:border-r">
                        <div>
                            <a href="/" class="inline-block bg-gradient-to-br from-gw-accent-hover via-gw-accent to-gw-accent-dark bg-clip-text text-3xl font-extrabold tracking-normal text-transparent">
                                GW<span class="font-black">Track</span>.com
                            </a>
                            <div class="mt-2 border-l-2 border-gw-accent pl-3 text-xs font-semibold uppercase tracking-[0.16em] text-gw-accent">
                                Financial Intelligence &middot; Insurance Team Portal
                            </div>
                        </div>

                        <div class="mt-8 overflow-hidden rounded-3xl border border-gw-accent/20 bg-[#070707] shadow-[0_18px_34px_-24px_rgba(132, 187, 65,0.55)]">
                            <img
                                src="{{ asset('images/authimage.jpg') }}"
                                alt="GWTrack registration"
                                class="h-64 w-full object-cover object-top sm:h-80 lg:h-[22rem]"
                                style="-webkit-mask-image: linear-gradient(to bottom, black 0%, black 62%, rgba(0,0,0,0.3) 100%); mask-image: linear-gradient(to bottom, black 0%, black 62%, rgba(0,0,0,0.3) 100%);"
                            >
                        </div>

                        <div class="mt-8">
                            <h1 class="max-w-lg bg-gradient-to-r from-white to-gw-accent-hover bg-clip-text text-3xl font-bold leading-tight text-transparent sm:text-4xl">
                                Create Your GWTrack Account
                            </h1>
                            <div class="mt-4 h-0.5 w-16 bg-gradient-to-r from-gw-accent to-transparent"></div>
                            <p class="mt-5 max-w-md border-l-2 border-gw-accent/50 pl-4 text-base leading-7 text-slate-300">
                                Start your onboarding, licensing, training, and field apprenticeship journey with your sponsor already verified.
                            </p>

                            <div class="mt-8 space-y-4 text-sm text-slate-200">
                                <div class="flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                    Premium associate dashboard and training path
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                    Mentor-led field apprenticeship tracking
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-2 w-2 rounded-full bg-gw-accent shadow-[0_0_8px_#84BB41]"></span>
                                    Licensing, rank advancement, and team resources
                                </div>
                            </div>

                            <div class="mt-8 rounded-2xl border border-gw-accent/25 bg-[#070707] p-5 shadow-[0_12px_28px_-20px_rgba(132, 187, 65,0.45)]" role="note">
                                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-gw-accent">Important Before Registering</div>
                                <p class="mt-3 text-sm leading-6 text-slate-300">
                                    You must already be registered with Greatway Financial Group before completing GWTrack registration. If you have not finished your Greatway enrollment, ask your sponsor how to proceed. Your Greatway sponsor must be the same person as your GWTrack sponsor&mdash;<span class="font-semibold text-white">{{ $invitation->sponsor->name }}</span>. If this is not the person who invited you, stop here and ask the correct sponsor to send their invitation link.
                                </p>
                            </div>

                        </div>
                    </aside>

                    <main class="bg-[#0a0a0c] p-6 sm:p-8">
                        <div class="mb-8">
                            <h2 class="text-2xl font-semibold text-white sm:text-3xl">Agent Registration</h2>
                            <p class="mt-2 text-sm text-slate-400">Secure invitation access &middot; GW associate verification required</p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('register') }}"
                            class="space-y-5"
                            x-data="{
                                editCountryId: @js($currentCountryId),
                                editProvinceId: @js($currentProvinceId),
                                editProvinces: @js($locationOptions['provincesByCountryId']),
                                get editProvinceOptions() {
                                    return this.editProvinces[String(this.editCountryId)] || {};
                                },
                                onCountryChange() {
                                    const options = this.editProvinceOptions;
                                    if (this.editProvinceId && ! Object.prototype.hasOwnProperty.call(options, this.editProvinceId)) {
                                        this.editProvinceId = '';
                                    }
                                },
                            }"
                        >
                            @csrf

                            <input type="hidden" name="registration_code" value="{{ old('registration_code', $invitation->code) }}">

                            <div>
                                <label for="registration_code_display" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Registration Code</label>
                                <input id="registration_code_display" type="text" value="{{ $invitation->code }}" disabled class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-200 shadow-sm">
                                <p class="mt-2 text-xs text-slate-400">Enter the portal only through the secure code provided by your sponsor or mentor.</p>
                                <x-input-error :messages="$errors->get('registration_code')" class="mt-2" />
                            </div>

                            <div>
                                <label for="sponsor_display" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Sponsor</label>
                                <input id="sponsor_display" type="text" value="{{ $invitation->sponsor->name }}" disabled class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-200 shadow-sm">
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="first_name" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">First Name <span class="text-red-400">*</span></label>
                                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required autofocus autocomplete="given-name" placeholder="James" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="last_name" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Last Name <span class="text-red-400">*</span></label>
                                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required autocomplete="family-name" placeholder="Carter" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Email <span class="text-red-400">*</span></label>
                                    @if ($invitation->hasInviteeEmail())
                                        <input type="hidden" name="email" value="{{ $invitation->email }}">
                                        <input
                                            id="email"
                                            type="email"
                                            value="{{ $invitation->email }}"
                                            disabled
                                            autocomplete="username"
                                            class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-200 shadow-sm"
                                        >
                                        <p class="mt-2 text-xs text-slate-400">
                                            This invitation is locked to <span class="font-semibold text-slate-200">{{ $invitation->email }}</span> and cannot be used with another email.
                                        </p>
                                    @else
                                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="new.member@example.com" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                                    @endif
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="gw_associate_id" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">GW Associate ID <span class="text-red-400">*</span></label>
                                    <input id="gw_associate_id" name="gw_associate_id" type="text" value="{{ old('gw_associate_id') }}" required autocomplete="off" placeholder="GW-1001" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                                    <p class="mt-2 text-xs text-slate-400">Only active Greatway Financial Group associates may complete registration.</p>
                                    <x-input-error :messages="$errors->get('gw_associate_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Password <span class="text-red-400">*</span></label>
                                    <x-password-input variant="guest" id="password" name="password" required autocomplete="new-password" placeholder="Create password" class="mt-2" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Confirm Password <span class="text-red-400">*</span></label>
                                    <x-password-input variant="guest" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm password" class="mt-2" />
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="country_id" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Country <span class="text-red-400">*</span></label>
                                    <select id="country_id" name="country_id" required x-model="editCountryId" @change="onCountryChange()" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 focus:border-gw-accent focus:ring-gw-accent">
                                        <option value="" disabled @selected($currentCountryId === '')>Select country</option>
                                        @foreach ($registrationCountries as $countryId => $countryName)
                                            <option value="{{ $countryId }}" @selected($currentCountryId === (string) $countryId)>{{ $countryName }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-xs text-slate-400">Required to match you with a CFM licensed in your jurisdiction.</p>
                                    <x-input-error :messages="$errors->get('country_id')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="timezone_id" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">Timezone <span class="text-red-400">*</span></label>
                                    <select id="timezone_id" name="timezone_id" required class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 focus:border-gw-accent focus:ring-gw-accent">
                                        <option value="" disabled @selected($currentTimezoneId === '')>Select your local timezone</option>
                                        @foreach ($timezones as $timezoneId => $timezoneLabel)
                                            <option value="{{ $timezoneId }}" @selected($currentTimezoneId === (string) $timezoneId)>{{ $timezoneLabel }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('timezone_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="state_province_id" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">State / Province <span class="text-red-400">*</span></label>
                                    <select
                                        id="state_province_id"
                                        name="state_province_id"
                                        x-model="editProvinceId"
                                        required
                                        :disabled="! editCountryId"
                                        class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 focus:border-gw-accent focus:ring-gw-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <option value="" disabled @selected($currentProvinceId === '')>Select state / province</option>
                                        @if ($provinceIsLegacy)
                                            <option value="{{ $currentProvinceId }}" selected>{{ $provinceOptions[$currentProvinceId] ?? 'Previously selected' }}</option>
                                        @endif
                                        <template x-for="(label, value) in editProvinceOptions" :key="value">
                                            <option :value="value" x-text="label"></option>
                                        </template>
                                    </select>
                                    <p class="mt-2 text-xs text-slate-400">Choose the state or province where you are licensed and will build your business.</p>
                                    <x-input-error :messages="$errors->get('state_province_id')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="city" class="block text-xs font-bold uppercase tracking-wide text-gw-accent">City <span class="text-red-400">*</span></label>
                                    <input id="city" name="city" type="text" value="{{ old('city') }}" required autocomplete="address-level2" placeholder="Vancouver / Manila / Mexico City" class="mt-2 block w-full rounded-2xl border border-[#2a2a2e] bg-[#131316] px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-gw-accent focus:ring-gw-accent">
                                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                                </div>
                            </div>

                            <div class="space-y-3 rounded-2xl border border-gw-accent/20 bg-[#131316] p-5">
                                <label class="flex gap-3 text-sm leading-6 text-slate-300">
                                    <input type="checkbox" name="sponsor_confirmed" value="1" class="mt-1 rounded border-gw-accent/40 bg-[#0a0a0c] text-gw-accent focus:ring-gw-accent" required @checked(old('sponsor_confirmed'))>
                                    <span>I confirm that {{ $invitation->sponsor->name }} is the person sponsoring my GWTrack registration. <span class="text-red-400">*</span></span>
                                </label>
                                <x-input-error :messages="$errors->get('sponsor_confirmed')" class="mt-2" />

                                <label class="flex gap-3 text-sm leading-6 text-slate-300">
                                    <input type="checkbox" name="active_associate_confirmed" value="1" class="mt-1 rounded border-gw-accent/40 bg-[#0a0a0c] text-gw-accent focus:ring-gw-accent" required @checked(old('active_associate_confirmed'))>
                                    <span>I confirm that I am an active associate of Greatway Financial Group. <span class="text-red-400">*</span></span>
                                </label>
                                <x-input-error :messages="$errors->get('active_associate_confirmed')" class="mt-2" />
                            </div>

                            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-gw-accent-dark via-gw-accent to-gw-accent-hover px-5 py-3 text-sm font-extrabold uppercase tracking-wider text-black shadow-lg transition hover:-translate-y-0.5 hover:shadow-gw-accent/20">
                                Create Account
                            </button>

                            <div class="border-t border-gw-accent/20 pt-5 text-center text-sm text-slate-400">
                                Already have an account?
                                <a href="{{ route('login') }}" class="font-semibold text-gw-accent hover:text-gw-accent-hover hover:underline">Sign in</a>
                            </div>

                            <p class="text-center text-xs text-slate-400">
                                <span class="text-red-400">*</span> Required
                            </p>
                        </form>
                    </main>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
