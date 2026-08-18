<div x-show="viewMode === 'compare'" x-cloak>
    <h3 class="mb-4 font-semibold text-gw-primary">Compare CFMs (max 3)</h3>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <template x-for="i in 3" :key="'cmp-' + i">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <select class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" x-model="compareCfms[i - 1]">
                    <option value="">Select CFM</option>
                    <template x-for="cfm in cfms" :key="'opt-' + i + '-' + cfm.id">
                        <option :value="cfm.id" x-text="cfm.name"></option>
                    </template>
                </select>
                <div x-show="compareCfms[i - 1]" class="mt-3 space-y-2 text-xs text-slate-600" x-text="getCompareDetails(i - 1)"></div>
            </div>
        </template>
    </div>
</div>
