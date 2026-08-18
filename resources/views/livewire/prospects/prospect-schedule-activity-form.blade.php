<div @class([
    'rounded-lg border border-gw-accent/40 bg-gw-accent-soft/50 p-4' => ! $embedded,
])>
    @unless ($embedded)
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">{{ ($isEditing ?? false) ? 'Edit Schedule' : 'Schedule Activity' }}</p>
                <p class="text-sm font-semibold text-gw-primary">{{ $activityTypeLabel }}</p>
            </div>
            <button type="button" wire:click="cancel" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
        </div>
    @endunless

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($quickScheduleTypes as $slug => $label)
            <button
                type="button"
                wire:key="schedule-quick-{{ $slug }}"
                wire:click="setQuickSchedule('{{ $slug }}')"
                class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-gw-accent"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <form wire:submit="save" class="grid gap-3 md:grid-cols-2">
        @foreach ($resolvedFields as $field)
            @php
                $colSpan = (int) ($field['col_span'] ?? 1);
                $fieldKey = $field['key'];
            @endphp

            @if (($field['type'] ?? '') === 'checkbox')
                <label @class(['flex items-start gap-2', 'md:col-span-2' => $colSpan >= 2])>
                    <input
                        type="checkbox"
                        wire:model="state.{{ $fieldKey }}"
                        class="mt-1 rounded border-slate-300 text-gw-primary focus:ring-gw-accent"
                    >
                    <span>
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $field['label'] }}</span>
                        @if (! empty($field['help']))
                            <span class="mt-1 block text-[11px] font-normal normal-case text-slate-500">{{ $field['help'] }}</span>
                        @endif
                    </span>
                </label>
                @error('state.'.$fieldKey) <p class="text-xs text-red-600 md:col-span-2">{{ $message }}</p> @enderror
                @continue
            @endif

            <label @class(['block', 'md:col-span-2' => $colSpan >= 2])>
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-600">*</span>
                    @endif
                </span>

                @switch($field['type'])
                    @case('select')
                        <select
                            wire:model{{ ($field['live'] ?? false) ? '.live' : '' }}="state.{{ $fieldKey }}"
                            class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                            @if (! empty($field['placeholder']))
                                <option value="">{{ $field['placeholder'] }}</option>
                            @endif
                            @foreach ($field['options'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @break

                    @case('select_creatable')
                        @php
                            $createFieldKey = $field['create_field_key'] ?? ($fieldKey.'_new');
                        @endphp
                        <select
                            wire:model.live="state.{{ $fieldKey }}"
                            class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                            @if (! empty($field['placeholder']))
                                <option value="">{{ $field['placeholder'] }}</option>
                            @endif
                            @foreach ($field['options'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                            <option value="{{ $createOptionValue }}">{{ $field['create_option_label'] ?? 'Add new…' }}</option>
                        </select>

                        @if (($state[$fieldKey] ?? null) === $createOptionValue)
                            <input
                                wire:model="state.{{ $createFieldKey }}"
                                type="text"
                                placeholder="{{ $field['create_placeholder'] ?? 'Enter a new option…' }}"
                                class="mt-2 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            >
                            @error('state.'.$createFieldKey) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @endif
                        @break

                    @case('textarea')
                        <textarea
                            wire:model="state.{{ $fieldKey }}"
                            rows="{{ $field['rows'] ?? 3 }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        ></textarea>
                        @break

                    @case('datetime')
                        <input
                            wire:model="state.{{ $fieldKey }}"
                            type="datetime-local"
                            class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                        @break

                    @case('number')
                        <input
                            wire:model="state.{{ $fieldKey }}"
                            type="number"
                            min="{{ $field['min'] ?? 15 }}"
                            max="{{ $field['max'] ?? 480 }}"
                            class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                        @break

                    @default
                        <input
                            wire:model="state.{{ $fieldKey }}"
                            type="text"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            class="mt-1 h-9 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        >
                @endswitch

                @if (! empty($field['help']))
                    <p class="mt-1 text-[11px] text-slate-500">{{ $field['help'] }}</p>
                @endif

                @error('state.'.$fieldKey) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
        @endforeach

        <div class="flex flex-wrap justify-end gap-2 md:col-span-2">
            <button type="button" wire:click="cancel" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-md bg-gw-primary px-3 py-2 text-xs font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ ($isEditing ?? false) ? 'Update schedule' : 'Schedule activity' }}</span>
                <span wire:loading wire:target="save">Scheduling...</span>
            </button>
        </div>
    </form>
</div>
