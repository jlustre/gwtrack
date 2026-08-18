<div x-show="activeView === 'calendar'" x-cloak class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-gw-primary" x-text="calendarLabel"></h3>
    </div>
    <div class="mb-2 grid grid-cols-7 gap-1">
        <template x-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']">
            <div class="py-1 text-center text-[11px] font-semibold text-slate-500" x-text="d"></div>
        </template>
    </div>
    <div class="grid grid-cols-7 gap-1">
        <template x-for="day in calendarDays" :key="day.key">
            <div :class="day.today ? 'border-gw-accent bg-gw-accent-soft' : 'border-slate-100 bg-slate-50'" class="min-h-[72px] rounded-md border p-1.5 sm:min-h-[80px]">
                <div :class="day.today ? 'font-semibold text-gw-primary' : 'text-slate-600'" class="mb-1 text-xs" x-text="day.n || ''"></div>
                <template x-for="(ev, evIndex) in day.events" :key="`${day.key}-${evIndex}`">
                    <div class="mb-0.5 truncate rounded border border-gw-accent/30 bg-gw-accent/10 px-1 py-0.5 text-[9px] font-medium text-gw-accent-dark sm:text-[10px]" x-text="ev"></div>
                </template>
            </div>
        </template>
    </div>
</div>
