@php
    $jurisdictionKey = \App\Support\LocationOptions::jurisdictionKey($country, $provinceValue);
    $isSelected = in_array($jurisdictionKey, $selectedKeys, true);
    $licenseNumberValue = old($licenseNumberInputName.'.'.$jurisdictionKey, $licenseNumbers[$jurisdictionKey] ?? '');
    $licenseExpirationValue = old($licenseExpirationInputName.'.'.$jurisdictionKey, $licenseExpirations[$jurisdictionKey] ?? '');
    $licenseNumberField = $licenseNumberWireModel
        ? $licenseNumberWireModel.'.'.$jurisdictionKey
        : $licenseNumberInputName.'.'.$jurisdictionKey;
    $licenseExpirationField = $licenseExpirationWireModel
        ? $licenseExpirationWireModel.'.'.$jurisdictionKey
        : $licenseExpirationInputName.'.'.$jurisdictionKey;
    $fieldId = \Illuminate\Support\Str::slug($jurisdictionKey);
@endphp

<div
    class="{{ $optionWrapperClass }}"
    x-data="{ selected: @js($isSelected) }"
>
    <label class="{{ $optionLabelClass }}">
        <input
            type="checkbox"
            @if ($wireModel)
                wire:model="{{ $wireModel }}"
                x-on:change="selected = $event.target.checked"
            @else
                name="{{ $checkboxName }}"
                x-model="selected"
            @endif
            value="{{ $jurisdictionKey }}"
            @checked($isSelected)
            class="{{ $checkboxClass }}"
        >
        <span>{{ \App\Support\LocationOptions::formatJurisdictionLabel($country, $provinceValue) }}</span>
    </label>

    @if ($showLicenseNumbers)
        <div x-show="selected" x-cloak class="mt-2 grid grid-cols-1 gap-2 pl-6 sm:grid-cols-2">
            <div>
                <label for="license-number-{{ $fieldId }}" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    License number <span class="text-red-500">*</span>
                </label>
                <input
                    id="license-number-{{ $fieldId }}"
                    type="text"
                    maxlength="100"
                    placeholder="Enter license number"
                    @if ($licenseNumberWireModel)
                        wire:model="{{ $licenseNumberWireModel }}.{{ $jurisdictionKey }}"
                    @else
                        name="{{ $licenseNumberInputName }}[{{ $jurisdictionKey }}]"
                        value="{{ $licenseNumberValue }}"
                    @endif
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                @error($licenseNumberField)
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="license-expiration-{{ $fieldId }}" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Expiration date <span class="text-red-500">*</span>
                </label>
                <input
                    id="license-expiration-{{ $fieldId }}"
                    type="date"
                    @if ($licenseExpirationWireModel)
                        wire:model="{{ $licenseExpirationWireModel }}.{{ $jurisdictionKey }}"
                    @else
                        name="{{ $licenseExpirationInputName }}[{{ $jurisdictionKey }}]"
                        value="{{ $licenseExpirationValue }}"
                    @endif
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                @error($licenseExpirationField)
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif
</div>
