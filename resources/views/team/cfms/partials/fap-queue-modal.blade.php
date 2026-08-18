<div
    x-show="showFapQueueModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-auto bg-slate-900/50 p-4 backdrop-blur-sm"
    @keydown.escape.window="showFapQueueModal = false"
>
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-xl border border-slate-200 bg-white shadow-xl" @click.outside="showFapQueueModal = false">
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h3 class="text-xl font-semibold text-gw-primary">FAP Assignment Queue</h3>
                <p class="mt-1 text-xs text-slate-500">Associates in your downline awaiting CFM assignment</p>
            </div>
            <button type="button" @click="showFapQueueModal = false" class="text-2xl leading-none text-slate-400 hover:text-gw-primary">&times;</button>
        </div>

        <div class="shrink-0 border-b border-slate-200 bg-slate-50 px-6 py-3">
            <div class="flex flex-wrap gap-4 text-sm">
                <span class="text-slate-600">Pending: <strong class="text-gw-primary" x-text="stats.pendingFap"></strong></span>
                <span class="text-slate-600">Showing: <strong class="text-gw-primary" x-text="fapQueue.length"></strong></span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div x-show="fapQueue.length === 0" class="py-10 text-center text-slate-500">
                <p class="text-sm">No associates are waiting for a CFM assignment.</p>
            </div>

            <div x-show="fapQueue.length > 0" class="space-y-3">
                <template x-for="associate in fapQueue" :key="'fap-' + associate.id">
                    <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-gw-primary" x-text="associate.name"></p>
                            <p x-show="associate.queueLabel" class="mt-0.5 text-xs text-gw-accent-dark" x-text="associate.queueLabel"></p>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="associate.email"></p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                <span class="rounded-full bg-gw-accent-soft px-2 py-0.5 text-gw-accent-dark" x-text="associate.rank"></span>
                                <span x-text="'Upline: ' + associate.sponsor"></span>
                                <span x-text="(associate.province && associate.province !== '—' ? associate.province + ', ' + (associate.country || 'Canada') + ' · ' : '') + associate.city + ' · ' + associate.timezone"></span>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a :href="associate.profileUrl" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-slate-50">View</a>
                            <button
                                type="button"
                                @click="selectAssociateForRecommendations(associate); showFapQueueModal = false"
                                class="rounded-lg border border-gw-accent/50 px-3 py-2 text-xs font-semibold text-gw-accent-dark transition hover:bg-gw-accent-soft"
                                :class="String(selectedRecommendationAssociateId) === String(associate.id) ? 'bg-gw-accent-soft' : ''"
                            >View Matches</button>
                            <button type="button" @click="assignFromQueue(associate)" class="rounded-lg bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent-hover">Assign CFM</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex shrink-0 gap-3 border-t border-slate-200 px-6 py-4">
            <button type="button" @click="showFapQueueModal = false" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">Close</button>
            <button type="button" @click="openAssign()" class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover">New Assignment</button>
        </div>
    </div>
</div>
