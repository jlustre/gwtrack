<div x-show="activeView === 'team'" x-cloak class="grid gap-3 sm:grid-cols-2">
    <template x-for="member in teamMembers" :key="member.name">
        <div class="rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] p-4 shadow-sm transition hover:border-gw-accent">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold" :class="member.avatarRing" x-text="member.initials"></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gw-primary" x-text="member.name"></p>
                    <p class="text-[11px] text-slate-500" x-text="member.role"></p>
                </div>
                <span class="text-xl font-bold text-gw-primary" x-text="member.taskCount"></span>
            </div>
            <div class="mb-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gw-accent" :style="`width:${member.completion}%`"></div></div>
            <div class="flex justify-between text-[11px] text-slate-500">
                <span><span :class="member.completion > 60 ? 'font-semibold text-emerald-700' : 'font-semibold text-amber-700'" x-text="member.completion + '%'"></span> complete</span>
                <span><span class="font-semibold text-red-700" x-text="member.overdue"></span> overdue</span>
            </div>
        </div>
    </template>
</div>
