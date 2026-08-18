<nav class="flex gap-1.5 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1.5 shadow-sm" aria-label="Task views">
    <template x-for="tab in viewTabs" :key="tab.id">
        <button
            type="button"
            @click="activeView = tab.id"
            :class="activeView === tab.id ? 'bg-gw-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-gw-primary'"
            class="flex shrink-0 items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition"
            x-text="tab.label"
        ></button>
    </template>
</nav>
