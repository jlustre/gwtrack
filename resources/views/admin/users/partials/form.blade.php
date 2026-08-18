@props([
    'managedUser' => null,
    'roles',
    'ranks',
    'teams',
    'sponsors',
    'mentors' => collect(),
    'locationOptions' => [],
    'profile' => null,
    'selectedRole' => null,
])

@php
    $selectedRole = old('role', $selectedRole ?? $managedUser?->getRoleNames()->first() ?? \App\Models\User::DEFAULT_ROLE);
    $profile = $profile ?? $managedUser?->profile;
    $contactTimes = $locationOptions['contactTimes'] ?? [];
    $currentContactTime = old('best_contact_time', $profile?->best_contact_time ?? '');
    $contactTimeIsLegacy = filled($currentContactTime) && ! array_key_exists($currentContactTime, $contactTimes);
    $selectedLicenses = old('insurance_licenses', $profile?->insurance_licenses ?? []);
    $isLicensedLifeInsurance = old(
        'is_licensed_life_insurance',
        $profile?->is_licensed_life_insurance ?? ($profile?->insurance_licenses ?? []) !== []
    );
@endphp

<div
    class="space-y-8"
    x-data="{
        licensedLifeInsurance: @js((bool) $isLicensedLifeInsurance),
        editCountryId: @js((string) old('country_id', $profile?->country_id ?? '')),
        editProvinceId: @js((string) old('state_province_id', $profile?->state_province_id ?? '')),
        editProvinces: @js($locationOptions['provincesByCountryId'] ?? []),
        get editProvinceOptions() {
            return this.editProvinces[String(this.editCountryId)] || {};
        },
        syncProvinceSelect(selectId = 'state_province_id') {
            const next = window.rebuildProvinceSelectOptions(selectId, this.editProvinceOptions, this.editProvinceId);
            if (next !== this.editProvinceId) {
                this.editProvinceId = next;
            }
        },
        onCountryChange() {
            this.syncProvinceSelect('state_province_id');
        },
    }"
    x-init="$nextTick(() => syncProvinceSelect('state_province_id'))"
>
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Account</h2>
        <p class="mt-1 text-sm text-slate-600">Login credentials, role, rank, team hierarchy, and account status.</p>

        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <div>
                <label for="name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                <input id="name" name="name" value="{{ old('name', $managedUser?->name) }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $managedUser?->email) }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $managedUser ? 'New Password' : 'Password' }}</label>
                <input id="password" name="password" type="password" @required(! $managedUser) class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @if ($managedUser)
                    <p class="mt-1 text-xs text-slate-500">Leave blank to keep the current password.</p>
                @endif
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" @required(! $managedUser) class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
            </div>

            <div>
                <label for="role" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div>
                <label for="rank_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</label>
                <select id="rank_id" name="rank_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">No rank</option>
                    @foreach ($ranks as $rank)
                        <option value="{{ $rank->id }}" @selected((string) old('rank_id', $managedUser?->rank_id) === (string) $rank->id)>{{ $rank->code }} - {{ $rank->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('rank_id')" class="mt-2" />
            </div>

            <div>
                <label for="team_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Team</label>
                <select id="team_id" name="team_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">No team</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id', $managedUser?->team_id) === (string) $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
            </div>

            <div>
                <label for="sponsor_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sponsor</label>
                <select id="sponsor_id" name="sponsor_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">No sponsor</option>
                    @foreach ($sponsors as $sponsor)
                        <option value="{{ $sponsor->id }}" @selected((string) old('sponsor_id', $managedUser?->sponsor_id) === (string) $sponsor->id)>{{ $sponsor->name }} - {{ $sponsor->email }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('sponsor_id')" class="mt-2" />
            </div>

            <div>
                <label for="mentor_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">CFM / Mentor</label>
                <select id="mentor_id" name="mentor_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">No mentor</option>
                    @foreach ($mentors as $mentor)
                        <option value="{{ $mentor->id }}" @selected((string) old('mentor_id', $managedUser?->mentor_id) === (string) $mentor->id)>{{ $mentor->name }} - {{ $mentor->email }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('mentor_id')" class="mt-2" />
            </div>

            <div>
                <label for="is_active" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account Status</label>
                <select id="is_active" name="is_active" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="1" @selected((string) old('is_active', (int) ($managedUser?->is_active ?? true)) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', (int) ($managedUser?->is_active ?? true)) === '0')>Inactive</option>
                </select>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
            </div>

            <div>
                <label for="joined_at" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Joined Date</label>
                <input id="joined_at" name="joined_at" type="date" value="{{ old('joined_at', $managedUser?->joined_at?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('joined_at')" class="mt-2" />
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Contact & Location</h2>
        <p class="mt-1 text-sm text-slate-600">Member contact details and regional settings shown on their profile.</p>

        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <div>
                <label for="phone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $profile?->phone) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div>
                <label for="best_contact_time" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Best Contact Time</label>
                <select id="best_contact_time" name="best_contact_time" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select best time to reach this member</option>
                    @if ($contactTimeIsLegacy)
                        <option value="{{ $currentContactTime }}" selected>{{ $currentContactTime }}</option>
                    @endif
                    @foreach ($contactTimes as $value => $label)
                        <option value="{{ $value }}" @selected($currentContactTime === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('best_contact_time')" class="mt-2" />
            </div>

            <div>
                <label for="city" class="text-xs font-semibold uppercase tracking-wide text-slate-500">City</label>
                <input id="city" name="city" type="text" value="{{ old('city', $profile?->city) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>

            @include('partials.profile-location-selects', [
                'locationOptions' => $locationOptions,
                'countryId' => $profile?->country_id,
                'stateProvinceId' => $profile?->state_province_id,
                'timezoneId' => $profile?->timezone_id,
                'selectClass' => 'mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent',
            ])

            <div class="lg:col-span-2">
                <label for="bio" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Member Bio</label>
                <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Goals, specialty, or mentorship focus">{{ old('bio', $profile?->bio) }}</textarea>
                <x-input-error :messages="$errors->get('bio')" class="mt-2" />
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">GW Details</h2>
        <p class="mt-1 text-sm text-slate-600">Greatway associate identifiers and recruitment information.</p>

        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <div>
                <label for="gw_associate_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">GW Associate ID</label>
                <input id="gw_associate_id" name="gw_associate_id" type="text" value="{{ old('gw_associate_id', $profile?->gw_associate_id) }}" placeholder="GW-1001" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('gw_associate_id')" class="mt-2" />
            </div>

            <div>
                <label for="license_number" class="text-xs font-semibold uppercase tracking-wide text-slate-500">License Number</label>
                <input id="license_number" name="license_number" type="text" value="{{ old('license_number', $profile?->license_number) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('license_number')" class="mt-2" />
            </div>

            <div class="lg:col-span-2">
                <label for="gw_invite_link" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Greatway Invite URL</label>
                <input id="gw_invite_link" name="gw_invite_link" type="url" value="{{ old('gw_invite_link', $profile?->gw_invite_link) }}" placeholder="https://greatwayfinancial.com/invite/..." class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('gw_invite_link')" class="mt-2" />
            </div>

            <div>
                <label for="recruited_at" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recruited Date</label>
                <input id="recruited_at" name="recruited_at" type="date" value="{{ old('recruited_at', $profile?->recruited_at?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <x-input-error :messages="$errors->get('recruited_at')" class="mt-2" />
            </div>

            <div class="flex items-end">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <input
                        type="checkbox"
                        name="is_gw_active_associate"
                        value="1"
                        class="mt-1 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                        @checked(old('is_gw_active_associate', $profile?->is_gw_active_associate ?? false))
                    >
                    <span>
                        <span class="text-sm font-semibold text-gw-primary">Active GW Associate</span>
                        <span class="mt-1 block text-xs text-slate-500">Member is currently active with Greatway.</span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Insurance Licenses</h2>
        <p class="mt-1 text-sm text-slate-600">Life insurance licensing status and jurisdictions.</p>

        <div class="mt-4 space-y-4">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <input
                    type="checkbox"
                    name="is_licensed_life_insurance"
                    value="1"
                    class="mt-1 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                    x-model="licensedLifeInsurance"
                    @checked($isLicensedLifeInsurance)
                >
                <span>
                    <span class="text-sm font-semibold text-gw-primary">Licensed in Life Insurance</span>
                    <span class="mt-1 block text-xs text-slate-500">Enable to record active life insurance license jurisdictions.</span>
                </span>
            </label>
            <x-input-error :messages="$errors->get('is_licensed_life_insurance')" class="mt-2" />

            <div x-show="licensedLifeInsurance" x-cloak>
                @include('cfm.partials.licensed-jurisdictions-picker', [
                    'locationOptions' => $locationOptions,
                    'selectedKeys' => is_array($selectedLicenses) ? $selectedLicenses : [],
                    'inputName' => 'insurance_licenses',
                    'title' => 'License jurisdictions',
                    'description' => 'Select every province or state where this member holds a life insurance license.',
                    'sectionClass' => 'rounded-lg border border-slate-200 bg-slate-50/80 p-4',
                    'labelClass' => 'text-sm font-semibold text-gw-primary',
                    'collapsibleCountries' => true,
                ])
                <x-input-error :messages="$errors->get('insurance_licenses')" class="mt-2" />
                @foreach ($errors->get('insurance_licenses.*') as $error)
                    <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    </section>
</div>
