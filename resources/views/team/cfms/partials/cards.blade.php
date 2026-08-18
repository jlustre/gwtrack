<div x-show="viewMode === 'cards'" x-cloak class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    <template x-for="cfm in filteredCfms" :key="'card-' + cfm.id">
        <div class="cursor-pointer rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-gw-accent/50 hover:shadow-md" @click="selectCfm(cfm)">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-gw-accent to-gw-accent-dark text-lg font-bold text-gw-primary" x-text="cfm.initials"></div>
                    <div>
                        <div class="font-bold text-gw-primary" x-text="cfm.name"></div>
                        <div class="text-xs text-gw-accent-dark" x-text="cfm.rank"></div>
                    </div>
                </div>
                <span class="rounded-full px-2 py-1 text-xs" :class="statusBadgeClass(cfm.statusColor)" x-text="cfm.statusText"></span>
            </div>
            <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-xs" :class="hierarchyBadgeClass(cfm)" x-text="cfm.hierarchy"></span>
            <p x-show="cfm.hierarchyNotice" class="mt-2 text-[10px] text-amber-700" x-text="cfm.hierarchyNotice"></p>
            <div class="mt-3 flex justify-between text-xs text-slate-500">
                <span>Active: <span class="font-medium text-gw-primary" x-text="cfm.activeApprentices + '/' + cfm.maxApprentices"></span></span>
                <span>Completion: <span class="font-medium text-emerald-700" x-text="cfm.completionRate + '%'"></span></span>
            </div>
            <div class="mt-2 h-1.5 w-full rounded-full bg-slate-200">
                <div class="h-1.5 rounded-full bg-gw-accent" :style="'width:' + loadWidth(cfm) + '%'"></div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="text-slate-500">Next: <span class="font-medium text-gw-primary" x-text="cfm.nextAvailable"></span></span>
                <span class="rounded-full px-2 py-1 font-bold" :class="scoreClass(cfm.score)" x-text="cfm.score + '/100'"></span>
            </div>
            <button type="button" @click.stop="openAssign(cfm)" class="mt-4 w-full rounded-lg border border-gw-accent/40 bg-gw-accent-soft py-2 text-sm font-semibold text-gw-accent-dark transition hover:bg-gw-accent/20">
                Assign Associate
            </button>
        </div>
    </template>
</div>
