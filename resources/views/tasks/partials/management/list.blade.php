<div x-show="['list','my','completed'].includes(activeView)" x-cloak class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 flex-1">
                <label for="task-table-search" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Quick search</label>
                <input
                    id="task-table-search"
                    type="search"
                    x-model.debounce.300ms="searchQuery"
                    placeholder="Search the task table..."
                    class="w-full rounded-md border-slate-300 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                />
            </div>
            <button
                type="button"
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-gw-primary transition hover:border-gw-accent"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                Advanced filters
                <span x-show="activeFilterCount > 0" class="rounded-full bg-gw-accent px-1.5 py-0.5 text-[10px] font-bold text-gw-primary" x-text="activeFilterCount"></span>
            </button>
        </div>
    </div>

    <div class="hidden border-b border-slate-200 bg-slate-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 lg:grid lg:grid-cols-12 lg:gap-3">
        <span class="lg:col-span-4">Task</span>
        <span class="lg:col-span-1">Priority</span>
        <span class="lg:col-span-1">Status</span>
        <span class="lg:col-span-2">Category</span>
        <span class="lg:col-span-1">Due</span>
        <span class="lg:col-span-1">Module</span>
        <span class="lg:col-span-1">Assign</span>
        <span class="lg:col-span-1 text-right">Actions</span>
    </div>

    <div x-show="filteredTasks.length === 0" x-cloak class="px-6 py-12 text-center">
        <p class="text-sm font-semibold text-gw-primary">No tasks match your filters</p>
        <p class="mt-2 text-xs text-slate-500">Try clearing filters or adjusting your search.</p>
        <button type="button" @click="clearFilters()" class="mt-4 rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-gw-accent hover:text-gw-primary">Clear filters</button>
    </div>

    <div x-show="filteredTasks.length > 0">
        <template x-for="task in paginatedTasks" :key="task.id">
            <article
                @click="selectTask(task)"
                :class="selectedTask?.id === task.id ? 'border-l-4 border-l-gw-accent bg-gw-accent-soft/60' : 'border-l-4 border-l-transparent'"
                class="cursor-pointer border-b border-slate-100 px-4 py-4 transition last:border-b-0 hover:bg-slate-50 lg:grid lg:grid-cols-12 lg:items-center lg:gap-3 lg:py-3.5"
            >
                <div class="lg:col-span-4">
                    <h3 class="text-sm font-semibold text-gw-primary" x-text="task.title"></h3>
                    <p class="mt-1 line-clamp-2 text-xs text-slate-600" x-text="task.desc"></p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 lg:hidden">
                        <span :class="priorityClass(task.priority)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="task.priority"></span>
                        <span :class="statusClass(task.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="task.status"></span>
                        <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[10px] font-bold text-gw-accent-dark" x-text="task.category"></span>
                    </div>
                    <div class="mt-2 h-1.5 w-full max-w-[140px] overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gw-accent" :style="`width:${task.progress}%`"></div>
                    </div>
                </div>
                <div class="mt-3 hidden lg:col-span-1 lg:block"><span :class="priorityClass(task.priority)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="task.priority"></span></div>
                <div class="hidden lg:col-span-1 lg:block"><span :class="statusClass(task.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="task.status"></span></div>
                <div class="hidden lg:col-span-2 lg:block"><span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[10px] font-bold text-gw-accent-dark" x-text="task.category"></span></div>
                <div class="hidden text-xs lg:col-span-1 lg:block" :class="task.status === 'Overdue' ? 'font-semibold text-red-700' : 'text-slate-600'" x-text="task.due"></div>
                <div class="hidden text-xs text-slate-600 lg:col-span-1 lg:block" x-text="task.module"></div>
                <div class="hidden lg:col-span-1 lg:flex">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-semibold" :class="task.avatarRing" x-text="task.initials"></div>
                </div>
                <div class="mt-3 flex items-center justify-end gap-2 lg:col-span-1 lg:mt-0" @click.stop>
                    <button type="button" @click="selectTask(task)" class="rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600 hover:border-gw-accent hover:text-gw-primary">View</button>
                    <button type="button" @click="openTask(task)" class="rounded-md bg-gw-accent px-2 py-1 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover" x-text="task.actionLabel"></button>
                </div>
            </article>
        </template>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <span x-text="paginationSummary"></span>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="prevPage()"
                :disabled="currentPage <= 1"
                class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-gw-accent hover:text-gw-primary disabled:cursor-not-allowed disabled:opacity-50"
            >
                Previous
            </button>
            <span class="px-1 text-xs text-slate-600">
                Page <span class="font-semibold text-gw-primary" x-text="Math.min(currentPage, totalPages)"></span>
                of <span class="font-semibold text-gw-primary" x-text="totalPages"></span>
            </span>
            <button
                type="button"
                @click="nextPage()"
                :disabled="currentPage >= totalPages"
                class="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-gw-accent hover:text-gw-primary disabled:cursor-not-allowed disabled:opacity-50"
            >
                Next
            </button>
        </div>
    </div>
</div>
