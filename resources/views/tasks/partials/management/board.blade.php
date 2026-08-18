<div x-show="activeView === 'board'" x-cloak class="-mx-1 flex gap-3 overflow-x-auto pb-2">
    <template x-for="col in kanbanCols" :key="col.id">
        <div class="min-w-[240px] flex-1 rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full" :class="col.dot"></span>
                    <span class="text-sm font-semibold text-gw-primary" x-text="col.label"></span>
                </div>
                <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 shadow-sm" x-text="col.tasks.length"></span>
            </div>
            <template x-for="t in col.tasks" :key="t.id">
                <div @click="selectTask(t)" class="mb-2.5 cursor-pointer rounded-md border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-gw-accent hover:shadow-md">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <p class="text-sm font-medium leading-snug text-gw-primary" x-text="t.title"></p>
                        <span :class="priorityClass(t.priority)" class="shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-semibold" x-text="t.priority"></span>
                    </div>
                    <span class="mb-2 inline-block rounded-full bg-gw-accent/15 px-2 py-0.5 text-[10px] font-bold text-gw-accent-dark" x-text="t.category"></span>
                    <div class="mb-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gw-accent" :style="`width:${t.progress}%`"></div></div>
                    <div class="flex items-center justify-between">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full text-[9px] font-semibold" :class="t.avatarRing" x-text="t.initials"></div>
                        <span class="text-[11px] text-slate-500" x-text="t.due"></span>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
