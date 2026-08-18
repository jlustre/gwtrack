<div class="bg-gw-primary px-6 py-6 text-white">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Task Management</p>
            <h1 class="mt-2 text-2xl font-semibold">Organize team priorities</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                Follow-ups, training actions, mentorship tasks, and rank advancement priorities in one place.
            </p>
            <p class="mt-3 text-xs text-slate-300">{{ $todayLabel }} · {{ $user->roles->pluck('name')->first() ?? 'member' }} · {{ $user->team?->name ?? 'Unassigned' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                Filters
                <span x-show="activeFilterCount > 0" class="rounded-full bg-gw-accent px-1.5 py-0.5 text-[10px] font-bold text-gw-primary" x-text="activeFilterCount"></span>
            </button>
            <button type="button" @click="activeView = 'my'" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-3.5 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">
                My Tasks
            </button>
            <button type="button" @click="activeView = 'team'" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-3.5 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">
                Team Tasks
            </button>
            <button type="button" onclick="Livewire.dispatch('open-my-tasks-modal', { createForm: true })" class="inline-flex items-center gap-1.5 rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                New Task
            </button>
        </div>
    </div>
</div>
