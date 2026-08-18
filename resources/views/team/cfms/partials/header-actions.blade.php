<div class="flex flex-wrap items-center gap-2">
    <button type="button" @click="openFapQueue()" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-3.5 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">
        View FAP Queue
    </button>
    <button type="button" @click="openAddCfm()" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-3.5 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">
        Add CFM
    </button>
    <button type="button" @click="openExport()" class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15">
        Export Report
    </button>
    <button type="button" @click="openAssign()" class="inline-flex items-center gap-1.5 rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        Assign New Associate
    </button>
</div>
