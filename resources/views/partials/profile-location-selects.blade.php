@props([
    'locationOptions',
    'countryId' => '',
    'stateProvinceId' => '',
    'timezoneId' => '',
    'countryModel' => 'editCountryId',
    'provinceModel' => 'editProvinceId',
    'provincesSource' => 'editProvinces',
    'provinceOptionsGetter' => 'editProvinceOptions',
    'countryChangeHandler' => 'onCountryChange()',
    'countryIdName' => 'country_id',
    'provinceIdName' => 'state_province_id',
    'timezoneIdName' => 'timezone_id',
    'selectClass' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gw-accent focus:ring-gw-accent',
    'countryInputId' => 'country_id',
    'provinceInputId' => 'state_province_id',
    'timezoneInputId' => 'timezone_id',
    'timezoneModel' => null,
])

@php
    $currentCountryId = (string) old('country_id', $countryId);
    $currentStateProvinceId = (string) old('state_province_id', $stateProvinceId);
    $currentTimezoneId = (string) old('timezone_id', $timezoneId);
    $provincesForCountry = $locationOptions['provincesByCountryId'][$currentCountryId] ?? [];
@endphp

<div>
    <x-input-label :for="$countryInputId" :value="__('Country')" />
    <select
        id="{{ $countryInputId }}"
        name="{{ $countryIdName }}"
        x-model="{{ $countryModel }}"
        @change="{{ $countryChangeHandler }}"
        class="{{ $selectClass }}"
    >
        <option value="">Select country</option>
        @foreach ($locationOptions['countries'] as $id => $name)
            <option value="{{ $id }}" @selected($currentCountryId === (string) $id)>{{ $name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('country_id')" />
</div>

<div>
    <x-input-label :for="$provinceInputId" :value="__('Province / State')" />
    <select
        id="{{ $provinceInputId }}"
        name="{{ $provinceIdName }}"
        x-model="{{ $provinceModel }}"
        class="{{ $selectClass }}"
    >
        <option value="">Select province / state</option>
        @foreach ($provincesForCountry as $id => $name)
            <option value="{{ $id }}" @selected($currentStateProvinceId !== '' && $currentStateProvinceId === (string) $id)>{{ $name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('state_province_id')" />
</div>

<div>
    <x-input-label :for="$timezoneInputId" :value="__('Timezone')" />
    <select
        id="{{ $timezoneInputId }}"
        name="{{ $timezoneIdName }}"
        @if ($timezoneModel) x-model="{{ $timezoneModel }}" @endif
        class="{{ $selectClass }}"
    >
        <option value="">Select timezone</option>
        @foreach ($locationOptions['timezones'] as $id => $label)
            <option value="{{ $id }}" @selected($currentTimezoneId === (string) $id)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('timezone_id')" />
</div>
