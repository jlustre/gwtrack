<div
    x-show="showExportModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-auto bg-slate-900/50 p-4 backdrop-blur-sm"
    @keydown.escape.window="showExportModal = false"
>
    <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl" @click.outside="showExportModal = false">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold text-gw-primary">Export CFM Report</h3>
                <p class="mt-1 text-xs text-slate-500">Download mentor directory and workload summary</p>
            </div>
            <button type="button" @click="showExportModal = false" class="text-2xl leading-none text-slate-400 hover:text-gw-primary">&times;</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-xs font-semibold text-slate-600">Export scope</label>
                <select x-model="exportForm.scope" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="filtered">Current filtered view</option>
                    <option value="all">All accessible CFMs</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Format</label>
                <select x-model="exportForm.format" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="csv">CSV spreadsheet</option>
                </select>
            </div>

            <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Include in export</p>
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="exportForm.includeStats" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent">
                    Summary dashboard stats
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="exportForm.includeWorkload" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent" checked disabled>
                    Workload &amp; readiness columns
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="exportForm.includeCalendar" class="rounded border-gray-300 text-gw-accent focus:ring-gw-accent" checked disabled>
                    Calendar availability columns
                </label>
            </div>

            <div class="rounded-xl border border-gw-accent/20 bg-gw-accent-soft p-3 text-xs text-slate-600">
                <span class="font-semibold text-gw-accent-dark" x-text="exportRows.length"></span> CFM record(s) will be exported.
                External hierarchy CFMs may include limited fields based on visibility permissions.
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="showExportModal = false" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">Cancel</button>
                <button type="button" @click="exportReport()" :disabled="exportRows.length === 0" class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-40">Download Report</button>
            </div>
        </div>
    </div>
</div>
