@if ($portal['canEditProfile'])
    @php
        $locationOptions = $portal['locationOptions'];
        $editForm = $portal['editForm'];
        $selectedLicensed = $editForm['licensed_jurisdictions'] ?? [];
        $profileFeedback = session('profile_feedback');
        $inputClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent';
        $inputErrorClass = 'border-red-400';
        $inputOkClass = 'border-gray-300';
    @endphp

    <div
        x-show="showEditProfileModal"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-auto bg-slate-900/50 p-4 backdrop-blur-sm"
        @keydown.escape.window="showEditProfileModal = false"
    >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 shadow-xl" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gw-primary">Edit CFM Profile</h3>
                    <p class="mt-1 text-xs text-slate-500">Update your contact details and mentor information</p>
                </div>
                <button type="button" @click="showEditProfileModal = false" class="text-2xl leading-none text-slate-400 hover:text-gw-primary" :disabled="profileSaving">&times;</button>
            </div>

            <form
                method="POST"
                action="{{ route('cfm.portal.profile.update') }}"
                class="space-y-3"
                @submit="submitProfileForm()"
            >
                @csrf
                @method('PATCH')

                @if ($profileFeedback && ($profileFeedback['type'] ?? '') === 'error')
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Could not save profile</p>
                        <p class="mt-1">{{ $profileFeedback['message'] }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Please fix the following</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="edit-phone" class="text-xs font-semibold text-slate-600">Phone</label>
                    <input
                        id="edit-phone"
                        type="text"
                        name="phone"
                        value="{{ $editForm['phone'] }}"
                        class="{{ $inputClass }} {{ $errors->has('phone') ? $inputErrorClass : $inputOkClass }}"
                    >
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="edit-city" class="text-xs font-semibold text-slate-600">City</label>
                    <input
                        id="edit-city"
                        type="text"
                        name="city"
                        value="{{ $editForm['city'] }}"
                        class="{{ $inputClass }} {{ $errors->has('city') ? $inputErrorClass : $inputOkClass }}"
                    >
                    @error('city')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @include('partials.profile-location-selects', [
                    'locationOptions' => $locationOptions,
                    'countryId' => $editForm['country_id'],
                    'stateProvinceId' => $editForm['state_province_id'],
                    'timezoneId' => $editForm['timezone_id'],
                    'selectClass' => $inputClass.' '.($errors->has('country_id') || $errors->has('state_province_id') || $errors->has('timezone_id') ? $inputErrorClass : $inputOkClass),
                    'countryInputId' => 'edit-country-id',
                    'provinceInputId' => 'edit-state-province-id',
                    'timezoneInputId' => 'edit-timezone-id',
                ])

                @include('cfm.partials.licensed-jurisdictions-picker', [
                    'locationOptions' => $locationOptions,
                    'selectedKeys' => $selectedLicensed,
                    'inputClass' => $inputClass,
                    'labelClass' => 'text-xs font-semibold text-slate-600',
                    'sectionClass' => 'rounded-lg border border-slate-200 bg-slate-50 p-3',
                ])

                <div>
                    <label for="edit-languages" class="text-xs font-semibold text-slate-600">Languages</label>
                    <input
                        id="edit-languages"
                        type="text"
                        name="languages"
                        value="{{ $editForm['languages'] }}"
                        placeholder="English, Spanish"
                        class="{{ $inputClass }} {{ $errors->has('languages') ? $inputErrorClass : $inputOkClass }}"
                    >
                    <p class="mt-1 text-xs text-slate-500">Separate multiple languages with commas.</p>
                    @error('languages')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="edit-specialties" class="text-xs font-semibold text-slate-600">Specialties</label>
                    <input
                        id="edit-specialties"
                        type="text"
                        name="specialties"
                        value="{{ $editForm['specialties'] }}"
                        placeholder="Field Apprenticeship, Licensing"
                        class="{{ $inputClass }} {{ $errors->has('specialties') ? $inputErrorClass : $inputOkClass }}"
                    >
                    <p class="mt-1 text-xs text-slate-500">Separate multiple specialties with commas.</p>
                    @error('specialties')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="edit-mentor-bio" class="text-xs font-semibold text-slate-600">Mentor bio</label>
                    <textarea
                        id="edit-mentor-bio"
                        name="mentor_bio"
                        rows="4"
                        placeholder="Share your mentoring approach and experience..."
                        class="{{ $inputClass }} {{ $errors->has('mentor_bio') ? $inputErrorClass : $inputOkClass }}"
                    >{{ $editForm['mentor_bio'] }}</textarea>
                    @error('mentor_bio')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="manual_unavailable" value="0">
                    <input
                        type="checkbox"
                        name="manual_unavailable"
                        value="1"
                        @checked($editForm['manual_unavailable'])
                        class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                    >
                    Mark me as temporarily unavailable for new assignments
                </label>
                @error('manual_unavailable')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="showEditProfileModal = false"
                        class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50 disabled:opacity-50"
                        :disabled="profileSaving"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="profileSaving"
                    >
                        <span x-show="! profileSaving">Save Profile</span>
                        <span x-show="profileSaving" x-cloak>Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
