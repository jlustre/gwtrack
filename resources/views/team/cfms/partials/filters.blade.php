<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end">
        <div class="min-w-[200px] flex-1">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input
                type="search"
                x-model="searchQuery"
                placeholder="Name, rank, location…"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
        </div>
        <div class="min-w-[160px]">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hierarchy</label>
            <select x-model="hierarchyFilter" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="All Accessible">All Accessible</option>
                <option value="My Hierarchy">My Hierarchy</option>
                <option value="External Hierarchy">External Hierarchy</option>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Workload</label>
            <select x-model="filterWorkload" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All</option>
                <option value="Available">Available</option>
                <option value="Moderate">Moderate</option>
                <option value="Busy">Busy</option>
                <option value="Overloaded">Overloaded</option>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Country</label>
            <select x-model="filterCountry" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All</option>
                <template x-for="c in countries" :key="c">
                    <option :value="c" x-text="c"></option>
                </template>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</label>
            <select x-model="filterRank" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All</option>
                <template x-for="r in ranks" :key="r">
                    <option :value="r" x-text="r"></option>
                </template>
            </select>
        </div>
        <button type="button" @click="clearFilters()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-slate-50 transition">
            Clear Filters
        </button>
    </div>
    <p class="mt-3 text-xs text-slate-500">
        Showing <span class="font-semibold text-gw-primary" x-text="filteredCfms.length"></span> of <span x-text="cfms.length"></span> CFMs
    </p>
</div>
