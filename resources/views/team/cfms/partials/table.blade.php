<div x-show="viewMode === 'table'" x-cloak class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-3 font-semibold">CFM</th>
                <th class="px-4 py-3 font-semibold">Hierarchy</th>
                <th class="px-4 py-3 font-semibold">Load / Status</th>
                <th class="px-4 py-3 font-semibold">Completion Rate</th>
                <th class="px-4 py-3 font-semibold">Overdue Tasks</th>
                <th class="px-4 py-3 font-semibold">Next Available</th>
                <th class="px-4 py-3 font-semibold">Score</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            <template x-for="cfm in filteredCfms" :key="cfm.id">
                <tr class="cursor-pointer transition hover:bg-slate-50" @click="selectCfm(cfm)">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gw-accent to-gw-accent-dark text-sm font-bold text-gw-primary" x-text="cfm.initials"></div>
                            <div>
                                <div class="font-medium text-gw-primary" x-text="cfm.name"></div>
                                <div class="text-xs text-slate-500" x-text="cfm.rank"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs" :class="hierarchyBadgeClass(cfm)" x-text="cfm.hierarchy"></span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex min-w-[140px] items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="statusDotClass(cfm.statusColor)"></span>
                            <span class="text-xs text-slate-600" x-text="cfm.activeApprentices + '/' + cfm.maxApprentices + ' apprentices'"></span>
                            <div class="h-1 max-w-[4rem] flex-1 rounded-full bg-slate-200">
                                <div class="h-1 rounded-full bg-gw-accent" :style="'width:' + loadWidth(cfm) + '%'"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3"><span class="font-medium text-emerald-700" x-text="cfm.completionRate + '%'"></span></td>
                    <td class="px-4 py-3" :class="cfm.overdueTasks > 0 ? 'font-medium text-red-700' : 'text-slate-500'" x-text="cfm.overdueTasks"></td>
                    <td class="px-4 py-3 text-xs text-slate-600" x-text="cfm.nextAvailable"></td>
                    <td class="px-4 py-3">
                        <div class="rounded-full px-2 py-1 text-center text-xs font-bold" :class="scoreClass(cfm.score)" x-text="cfm.score + '/100'"></div>
                    </td>
                    <td class="px-4 py-3">
                        <button type="button" @click.stop="openAssign(cfm)" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-accent">Assign</button>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

<div x-show="viewMode === 'table'" x-cloak class="grid gap-4 md:hidden">
    <template x-for="cfm in filteredCfms" :key="'mt-' + cfm.id">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" @click="selectCfm(cfm)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-gw-accent to-gw-accent-dark text-sm font-bold text-gw-primary" x-text="cfm.initials"></div>
                    <div>
                        <div class="font-medium text-gw-primary" x-text="cfm.name"></div>
                        <div class="text-xs text-slate-500" x-text="cfm.rank"></div>
                    </div>
                </div>
                <span class="rounded-full px-2 py-1 text-xs font-bold" :class="scoreClass(cfm.score)" x-text="cfm.score"></span>
            </div>
            <div class="mt-3 flex justify-between text-xs text-slate-500">
                <span x-text="cfm.hierarchy"></span>
                <span x-text="cfm.activeApprentices + '/' + cfm.maxApprentices"></span>
            </div>
            <button type="button" @click.stop="openAssign(cfm)" class="mt-3 w-full rounded-lg border border-gw-accent/40 bg-gw-accent-soft py-2 text-sm font-semibold text-gw-accent-dark">Assign</button>
        </div>
    </template>
</div>
