<div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
    <button
        type="button"
        @click="setView('table')"
        :class="viewMode === 'table' ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-50'"
        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition"
        :aria-pressed="viewMode === 'table'"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        Table
    </button>
    <button
        type="button"
        @click="setView('cards')"
        :class="viewMode === 'cards' ? 'bg-gw-primary text-white' : 'text-slate-600 hover:bg-slate-50'"
        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition"
        :aria-pressed="viewMode === 'cards'"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
        </svg>
        Cards
    </button>
</div>
