@php
    $selectedKeys = $selectedKeys ?? [];
    $provincesByCountry = $locationOptions['provincesByCountry'] ?? [];
    $inputName = $inputName ?? 'licensed_jurisdictions';
    $title = $title ?? 'Licensed provinces / states';
    $description = $description ?? 'Select every jurisdiction where you hold a life insurance license. Apprentices are matched to CFMs licensed in their province or state.';
    $checkboxName = str_ends_with($inputName, '[]') ? $inputName : $inputName.'[]';
    $inputClass = $inputClass ?? 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent';
    $labelClass = $labelClass ?? 'text-xs font-semibold text-slate-600';
    $sectionClass = $sectionClass ?? 'rounded-lg border border-slate-200 bg-slate-50 p-3';
    $checkboxClass = $checkboxClass ?? 'mt-0.5 rounded border-gray-300 text-gw-accent focus:ring-gw-accent';
    $countryLabelClass = $countryLabelClass ?? 'text-xs font-semibold text-gw-primary';
    $optionLabelClass = $optionLabelClass ?? 'flex items-start gap-2 text-xs text-slate-700 cursor-pointer';
    $collapsibleCountries = $collapsibleCountries ?? true;
    $wireModel = $wireModel ?? null;

    $countryPanels = [];
    foreach ($locationOptions['countries'] ?? [] as $country) {
        $provinces = $provincesByCountry[$country] ?? [];
        if ($provinces === []) {
            continue;
        }

        $countryKey = \Illuminate\Support\Str::slug($country);
        $jurisdictionKeys = [];
        foreach ($provinces as $provinceValue => $provinceLabel) {
            $jurisdictionKeys[] = \App\Support\LocationOptions::jurisdictionKey($country, $provinceValue);
        }

        $selectedCount = count(array_intersect($jurisdictionKeys, $selectedKeys));
        $countryPanels[] = [
            'country' => $country,
            'key' => $countryKey,
            'provinces' => $provinces,
            'selectedCount' => $selectedCount,
            'expanded' => $selectedCount > 0,
        ];
    }

    $initialExpanded = collect($countryPanels)
        ->mapWithKeys(fn (array $panel): array => [$panel['key'] => $panel['expanded']])
        ->all();
@endphp

<div class="{{ $sectionClass }}">
    <p class="{{ $labelClass }}">{{ $title }}</p>
    <p class="mt-1 mb-3 text-xs text-slate-500">
        {{ $description }}
    </p>

    @if ($collapsibleCountries)
        <div class="space-y-2" x-data="{ expanded: @js($initialExpanded) }">
            @foreach ($countryPanels as $panel)
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                        @click="expanded['{{ $panel['key'] }}'] = ! expanded['{{ $panel['key'] }}']"
                        :aria-expanded="expanded['{{ $panel['key'] }}'] ? 'true' : 'false'"
                    >
                        <div class="min-w-0">
                            <p class="{{ $countryLabelClass }}">{{ $panel['country'] }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                {{ count($panel['provinces']) }} {{ str('region')->plural(count($panel['provinces'])) }}
                                @if ($panel['selectedCount'] > 0)
                                    · {{ $panel['selectedCount'] }} selected
                                @endif
                            </p>
                        </div>
                        <svg
                            class="h-4 w-4 shrink-0 text-slate-500 transition-transform"
                            :class="expanded['{{ $panel['key'] }}'] ? 'rotate-180' : ''"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="expanded['{{ $panel['key'] }}']"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="border-t border-slate-100 px-3 py-3"
                    >
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($panel['provinces'] as $provinceValue => $provinceLabel)
                                @php
                                    $jurisdictionKey = \App\Support\LocationOptions::jurisdictionKey($panel['country'], $provinceValue);
                                @endphp
                                <label class="{{ $optionLabelClass }}">
                                    <input
                                        type="checkbox"
                                        @if ($wireModel)
                                            wire:model="{{ $wireModel }}"
                                        @else
                                            name="{{ $checkboxName }}"
                                        @endif
                                        value="{{ $jurisdictionKey }}"
                                        @checked(in_array($jurisdictionKey, $selectedKeys, true))
                                        class="{{ $checkboxClass }}"
                                    >
                                    <span>{{ \App\Support\LocationOptions::formatJurisdictionLabel($panel['country'], $provinceValue) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="max-h-56 space-y-4 overflow-y-auto pr-1">
            @foreach ($countryPanels as $panel)
                <div>
                    <p class="{{ $countryLabelClass }} mb-2">{{ $panel['country'] }}</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($panel['provinces'] as $provinceValue => $provinceLabel)
                            @php
                                $jurisdictionKey = \App\Support\LocationOptions::jurisdictionKey($panel['country'], $provinceValue);
                            @endphp
                            <label class="{{ $optionLabelClass }}">
                                <input
                                    type="checkbox"
                                    @if ($wireModel)
                                        wire:model="{{ $wireModel }}"
                                    @else
                                        name="{{ $checkboxName }}"
                                    @endif
                                    value="{{ $jurisdictionKey }}"
                                    @checked(in_array($jurisdictionKey, $selectedKeys, true))
                                    class="{{ $checkboxClass }}"
                                >
                                <span>{{ \App\Support\LocationOptions::formatJurisdictionLabel($panel['country'], $provinceValue) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @error($wireModel ?: $inputName)
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
    @error(($wireModel ?: $inputName).'.*')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
