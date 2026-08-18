@props([
    'tableKey',
    'columns' => [],
    'rows' => [],
    'empty' => 'No records to display yet.',
    'emptyFiltered' => 'No records match your search or filters.',
    'searchPlaceholder' => 'Search…',
    'filterFields' => [],
    'searchKeys' => [],
    'sumKey' => null,
    'sumLabel' => null,
])

<div
    x-data="profileTableFilter(@js($rows), {
        searchKeys: @js($searchKeys),
        filterFields: @js($filterFields),
        sumKey: @js($sumKey),
    })"
    class="space-y-4"
>
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4 sm:flex-row sm:flex-wrap sm:items-end">
        <div class="min-w-[200px] flex-1">
            <label :for="'search-{{ $tableKey }}'" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input
                :id="'search-{{ $tableKey }}'"
                type="search"
                x-model="searchQuery"
                placeholder="{{ $searchPlaceholder }}"
                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
        </div>

        @foreach ($filterFields as $field)
            <div class="min-w-[140px] sm:w-auto">
                <label :for="'filter-{{ $tableKey }}-{{ $field['key'] }}'" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $field['label'] }}</label>
                <select
                    :id="'filter-{{ $tableKey }}-{{ $field['key'] }}'"
                    x-model="filters['{{ $field['key'] }}']"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
                    <template x-for="option in optionsForField(@js($field))" :key="'{{ $tableKey }}-{{ $field['key'] }}-' + option.value">
                        <option :value="option.value" x-text="option.label"></option>
                    </template>
                </select>
            </div>
        @endforeach

        <button
            type="button"
            x-show="hasActiveFilters"
            x-cloak
            @click="clearFilters()"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
        >
            Clear filters
        </button>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
        <p>
            Showing <span class="font-semibold text-gw-primary" x-text="filteredCount"></span>
            of <span x-text="totalCount"></span>
            <span x-show="hasActiveFilters" x-cloak> (filtered)</span>
        </p>
        @if ($sumLabel && $sumKey)
            <p class="font-semibold text-gw-primary">
                {{ $sumLabel }}:
                <span x-text="'$' + filteredSum.toLocaleString()"></span>
                <span x-show="hasActiveFilters" x-cloak class="font-normal text-slate-500">filtered</span>
            </p>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <template x-for="(row, index) in filteredRows" :key="'{{ $tableKey }}-row-' + index">
                    <tr class="hover:bg-gw-accent-soft/40">
                        @foreach ($columns as $column)
                            @php
                                $columnKey = $column['key'] ?? '';
                                $columnType = $column['type'] ?? 'text';
                                $isStatus = $columnKey === 'status';
                            @endphp
                            <td class="px-4 py-3 text-gw-primary">
                                @if ($columnType === 'member')
                                    <div class="flex items-start gap-3">
                                        <template x-if="row.profile_photo_url">
                                            <img :src="row.profile_photo_url" :alt="(row.name || 'Member') + ' photo'" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-gw-accent/40">
                                        </template>
                                        <template x-if="! row.profile_photo_url">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gw-primary text-xs font-bold text-gw-accent" x-text="(row.name || 'M').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'M'"></span>
                                        </template>
                                        <div class="min-w-0">
                                            <div class="font-semibold" x-text="row.name ?? '—'"></div>
                                            <div class="text-xs text-slate-500" x-text="row.email ?? '—'"></div>
                                            <div class="text-xs text-slate-500" x-show="row.phone && row.phone !== '—'" x-text="row.phone"></div>
                                        </div>
                                    </div>
                                @elseif ($columnType === 'location')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span x-text="row.province ?? '—'"></span>
                                        <span
                                            class="inline-flex h-5 min-w-7 items-center justify-center rounded-full border border-gw-accent/50 bg-gw-accent-muted px-2 text-[10px] font-bold text-gw-primary"
                                            x-text="row.country_flag ?? 'GL'"
                                        ></span>
                                    </div>
                                @elseif ($columnType === 'role')
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="{
                                            'bg-gw-primary text-gw-accent': row.role === 'AO',
                                            'bg-gw-accent-muted text-gw-accent-dark': row.role === 'CFM',
                                            'bg-slate-100 text-slate-600': row.role === 'Member',
                                        }"
                                        x-text="row.role ?? '—'"
                                    ></span>
                                @elseif ($isStatus)
                                    <span
                                        x-show="row.status_key"
                                        x-cloak
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': row.status_key === 'completed',
                                            'bg-amber-100 text-amber-700': row.status_key === 'pending_confirmation',
                                            'bg-red-100 text-red-700': row.status_key === 'rejected',
                                            'bg-emerald-100 text-emerald-700': row.status_key === 'active',
                                            'bg-slate-100 text-slate-600': row.status_key === 'inactive',
                                            'bg-slate-100 text-slate-600': !['completed','pending_confirmation','rejected','active','inactive'].includes(row.status_key),
                                        }"
                                        x-text="row.status"
                                    ></span>
                                    <span x-show="! row.status_key" x-text="row.status ?? '—'"></span>
                                @else
                                    <span x-text="row['{{ $columnKey }}'] ?? '—'"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </template>
                <tr x-show="filteredRows.length === 0 && totalCount > 0" x-cloak>
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-slate-500">{{ $emptyFiltered }}</td>
                </tr>
                <tr x-show="totalCount === 0">
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-slate-500">{{ $empty }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
