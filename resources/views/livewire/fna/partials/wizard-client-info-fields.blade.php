@php
    $requiredFields = $requiredFields ?? [];
    $nameLabel = $nameLabel ?? 'Client Name';
    $isRequired = fn (string $field): bool => in_array($field, $requiredFields, true);
    $star = fn (string $field): string => $isRequired($field) ? ' *' : '';
@endphp

<div class="sm:col-span-2">
    <label class="{{ $labelClass }}">{{ $nameLabel }}{{ $star('client_name') }}</label>
    <input wire:model.live.debounce.750ms="client_name" class="{{ $inputClass }}" @if ($isRequired('client_name')) required @endif>
    @error('client_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Email{{ $star('client_email') }}</label>
    <input type="email" wire:model.live.debounce.750ms="client_email" class="{{ $inputClass }}" @if ($isRequired('client_email')) required @endif>
    @error('client_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Phone{{ $star('client_phone') }}</label>
    <input type="tel" wire:model.live.debounce.750ms="client_phone" class="{{ $inputClass }}" @if ($isRequired('client_phone')) required @endif>
    @error('client_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Date of Birth{{ $star('date_of_birth') }}</label>
    <input type="date" wire:model.live.debounce.750ms="date_of_birth" class="{{ $inputClass }}" @if ($isRequired('date_of_birth')) required @endif>
    @error('date_of_birth') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Gender{{ $star('gender') }}</label>
    <select wire:model.live="gender" class="{{ $inputClass }}" @if ($isRequired('gender')) required @endif>
        <option value="">Select gender</option>
        @foreach ($genders as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Marital Status{{ $star('marital_status') }}</label>
    <select wire:model.live="marital_status" class="{{ $inputClass }}" @if ($isRequired('marital_status')) required @endif>
        <option value="">Select marital status</option>
        @foreach ($maritalStatuses as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('marital_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Occupation</label>
    <input wire:model.live.debounce.750ms="occupation" class="{{ $inputClass }}">
    @error('occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Employer / Business</label>
    <input wire:model.live.debounce.750ms="employer_business" class="{{ $inputClass }}">
    @error('employer_business') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">City</label>
    <input wire:model.live.debounce.750ms="city" class="{{ $inputClass }}">
    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Country{{ $star('country') }}</label>
    <select wire:model.live="country" class="{{ $inputClass }}" @if ($isRequired('country')) required @endif>
        <option value="">Select country</option>
        @foreach ($countries as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">State / Province{{ $star('state_province') }}</label>
    <select
        wire:model.live="state_province"
        class="{{ $inputClass }}"
        @disabled(blank($country))
        @if ($isRequired('state_province')) required @endif
    >
        <option value="">{{ filled($country) ? 'Select state / province' : 'Select country first' }}</option>
        @foreach ($stateProvinces as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('state_province') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Preferred Contact{{ $star('preferred_contact_method') }}</label>
    <select wire:model.live="preferred_contact_method" class="{{ $inputClass }}" @if ($isRequired('preferred_contact_method')) required @endif>
        <option value="">Select preferred contact</option>
        @foreach ($preferredContactMethods as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('preferred_contact_method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
<div>
    <label class="{{ $labelClass }}">Best Contact Time{{ $star('best_contact_time') }}</label>
    <select wire:model.live="best_contact_time" class="{{ $inputClass }}" @if ($isRequired('best_contact_time')) required @endif>
        <option value="">Select best contact time</option>
        @foreach ($contactTimes as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @error('best_contact_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
