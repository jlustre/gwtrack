@php
    $fieldId = trim(($fieldIdPrefix ?? '').'_'.$field['name'], '_');
    $value = $fieldValue($field['name']);
@endphp

<template x-if="contentMode === 'compose'">
    <div>
        <label for="{{ $fieldId }}" class="block text-sm font-semibold text-gw-primary">{{ $field['label'] }}</label>
        @if (! empty($field['help']))
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $field['help'] }}</p>
        @endif
        <input
            id="{{ $fieldId }}"
            name="{{ $field['name'] }}"
            type="text"
            value="{{ $value }}"
            class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
        >
        @error($field['name'])
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</template>
