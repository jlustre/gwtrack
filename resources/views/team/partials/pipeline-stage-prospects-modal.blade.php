<div
    x-show="stageModalOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
    x-on:keydown.escape.window="closeStageProspects()"
>
    <div
        class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
        x-on:click.stop
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Pipeline Stage</p>
                <h3 class="mt-1 text-xl font-semibold" x-text="stageModalLabel || 'Prospects'"></h3>
                <p class="mt-1 text-sm text-slate-300">Prospects and clients currently in this stage.</p>
            </div>
            <button type="button" x-on:click="closeStageProspects()" class="text-2xl leading-none text-slate-300 transition hover:text-white">&times;</button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            <div x-show="stageModalLoading" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                Loading prospects...
            </div>

            <div x-show="stageModalError && ! stageModalLoading" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="stageModalError"></div>

            <div x-show="! stageModalLoading && ! stageModalError && stageModalProspects.length === 0" x-cloak class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No prospects are currently in this stage.
            </div>

            <div x-show="! stageModalLoading && stageModalProspects.length > 0" x-cloak class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Prospect</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Interest</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Profile</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="prospect in stageModalProspects" :key="'pipeline-stage-prospect-' + prospect.id">
                            <tr class="hover:bg-gw-accent-soft/40">
                                <td class="px-4 py-3 font-semibold text-gw-primary" x-text="prospect.name"></td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div x-text="prospect.email || '—'"></div>
                                    <div class="text-xs text-slate-500" x-text="prospect.phone || '—'"></div>
                                </td>
                                <td class="px-4 py-3 text-slate-600" x-text="prospect.interest_level"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="prospect.status"></td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="prospect.profile_url" class="text-sm font-semibold text-gw-accent-dark hover:text-gw-primary">View</a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
