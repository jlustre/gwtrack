<div x-show="showFilters" x-transition class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-2">
            <label for="task-search-query" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Search tasks</label>
            <input
                id="task-search-query"
                type="search"
                x-model.debounce.300ms="searchQuery"
                placeholder="Search title, category, assignee, notes..."
                class="w-full rounded-md border-slate-300 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            />
        </div>
        <div>
            <label for="task-filter-status" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Status</label>
            <select id="task-filter-status" x-model="filterStatus" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Status</option>
                <option>To Do</option>
                <option>In Progress</option>
                <option>Waiting</option>
                <option>Completed</option>
                <option>Cancelled</option>
                <option>Overdue</option>
            </select>
        </div>
        <div>
            <label for="task-filter-priority" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Priority</label>
            <select id="task-filter-priority" x-model="filterPriority" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Priority</option>
                <option>Low</option>
                <option>Medium</option>
                <option>High</option>
                <option>Urgent</option>
            </select>
        </div>
        <div>
            <label for="task-filter-assignee" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Assigned To</label>
            <select id="task-filter-assignee" x-model="filterAssignee" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Members</option>
                <template x-for="assignee in assigneeOptions" :key="assignee">
                    <option :value="assignee" x-text="assignee"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="task-filter-category" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Category</label>
            <select id="task-filter-category" x-model="filterCategory" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Categories</option>
                <template x-for="cat in categories" :key="cat">
                    <option :value="cat" x-text="cat"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="task-filter-due-date" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Due Date</label>
            <input id="task-filter-due-date" type="date" x-model="filterDueDate" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" />
        </div>
        <div>
            <label for="task-filter-module" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Related Module</label>
            <select id="task-filter-module" x-model="filterModule" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Modules</option>
                <option>Prospects</option>
                <option>Licensing</option>
                <option>Training</option>
                <option>Team</option>
                <option>Rank Advancement</option>
                <option>Profile</option>
                <option>Resources</option>
                <option>Admin</option>
            </select>
        </div>
        <div>
            <label for="task-page-size" class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Rows Per Page</label>
            <select id="task-page-size" x-model.number="pageSize" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">
            <span x-show="activeFilterCount > 0">
                <span x-text="activeFilterCount"></span> active filter<span x-show="activeFilterCount !== 1">s</span>
            </span>
            <span x-show="activeFilterCount === 0">Use filters to narrow the task table.</span>
        </p>
        <div class="flex justify-end gap-2">
            <button type="button" @click="clearFilters()" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-gw-accent hover:text-gw-primary">Clear Filters</button>
        </div>
    </div>
</div>
