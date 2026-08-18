@php
    $fc = 'mt-0.5 block w-full rounded-md border-slate-300 bg-white px-2.5 py-1.5 text-sm shadow-sm';
    $lc = 'text-xs font-semibold text-slate-600';
    $grid = 'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5';
@endphp

<div class="{{ $grid }}">
    <label class="block">
        <span class="{{ $lc }}">First Name <span class="text-red-600">*</span></span>
        <input wire:model="first_name" class="{{ $fc }}" autocomplete="given-name">
        @error('first_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Last Name</span>
        <input wire:model="last_name" class="{{ $fc }}" autocomplete="family-name">
        @error('last_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Mobile</span>
        <input wire:model="phone" type="tel" class="{{ $fc }}" autocomplete="tel">
        @error('phone') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Email</span>
        <input wire:model="email" type="email" class="{{ $fc }}" autocomplete="email">
        @error('email') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Funnel Type <span class="text-red-600">*</span></span>
        <select wire:model.live="funnel_type" class="{{ $fc }}">
            @foreach ($funnelTypes as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('funnel_type') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Pipeline Stage</span>
        <select wire:model="pipeline_stage_id" class="{{ $fc }}">
            <option value="">Select stage</option>
            @foreach ($stages as $stage)
                <option value="{{ $stage['id'] }}">{{ $stage['label'] }}</option>
            @endforeach
        </select>
        @error('pipeline_stage_id') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Lead Source</span>
        <select wire:model="prospect_source_id" class="{{ $fc }}">
            <option value="">No source</option>
            @foreach ($sources as $source)
                <option value="{{ $source->id }}">{{ $source->name }}</option>
            @endforeach
        </select>
        @error('prospect_source_id') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Interest Level <span class="text-red-600">*</span></span>
        <select wire:model="interest_level" class="{{ $fc }}">
            @foreach (['cold', 'warm', 'hot'] as $level)
                <option value="{{ $level }}">{{ str($level)->title() }}</option>
            @endforeach
        </select>
        @error('interest_level') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>

    <label class="block">
        <span class="{{ $lc }}">Priority <span class="text-red-600">*</span></span>
        <select wire:model="priority" class="{{ $fc }}">
            @foreach (['low', 'medium', 'high', 'urgent'] as $priorityOption)
                <option value="{{ $priorityOption }}">{{ str($priorityOption)->title() }}</option>
            @endforeach
        </select>
        @error('priority') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
    </label>
</div>
