<div
    x-show="modalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dashboard-stat-modal-title"
    @keydown.escape.window="closeModal()"
>
    <div
        class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-xl"
        x-on:click.outside="closeModal()"
    >
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent" x-text="modalScopeLabel"></p>
                <h2 id="dashboard-stat-modal-title" class="text-xl font-semibold text-gw-primary" x-text="modalTitle"></h2>
                <p class="mt-1 text-sm text-slate-500" x-show="modalSummary" x-text="modalSummary"></p>
            </div>
            <button
                type="button"
                class="gw-icon-btn-close"
                x-on:click="closeModal()"
                aria-label="Close details"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto px-6 py-4">
            <template x-if="loading">
                <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    Loading details...
                </div>
            </template>

            <template x-if="! loading && error">
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-6 text-center text-sm text-red-700" x-text="error"></div>
            </template>

            <template x-if="! loading && ! error && display === 'progress' && members.length === 0">
                <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    No members match this tracker in your current scope.
                </div>
            </template>

            <template x-if="! loading && ! error && display === 'progress' && members.length > 0">
                <div class="overflow-x-auto rounded-md border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Member</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 min-w-[12rem]">Completion</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="member in members" :key="member.id">
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gw-primary" x-text="member.name"></div>
                                        <div class="text-xs text-slate-500" x-text="member.email"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full border border-gw-accent bg-gw-accent-muted px-2 py-0.5 text-xs font-bold text-gw-primary" x-text="member.rank"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 min-w-[8rem] flex-1 rounded-full bg-slate-100">
                                                <div
                                                    class="h-2 rounded-full bg-gw-accent transition-all duration-300"
                                                    :style="`width: ${Math.max(0, Math.min(100, member.percent))}%`"
                                                ></div>
                                            </div>
                                            <span class="w-10 shrink-0 text-right text-xs font-semibold tabular-nums text-gw-primary" x-text="member.percent + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="member.status === 'Complete'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : (member.status === 'Not started'
                                                    ? 'bg-slate-100 text-slate-600'
                                                    : 'bg-gw-accent/20 text-gw-primary')"
                                            x-text="member.status"
                                        ></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <template x-if="! loading && ! error && display === 'progress' && items.length > 0">
                <div class="mt-5">
                    <h3 class="mb-3 text-sm font-semibold text-gw-primary">Breakdown</h3>
                    <div class="overflow-x-auto rounded-md border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gw-primary" x-text="item.title"></td>
                                        <td class="px-4 py-3 text-slate-600" x-text="item.subtitle ?? '—'"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="item.meta ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <template x-if="! loading && ! error && display === 'list' && items.length === 0">
                <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    No records to show for this stat yet.
                </div>
            </template>

            <template x-if="! loading && ! error && display === 'list' && items.length > 0">
                <div class="overflow-x-auto rounded-md border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Details</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Meta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-4 py-3">
                                        <template x-if="item.url">
                                            <a :href="item.url" class="font-semibold text-gw-primary hover:text-gw-accent" x-text="item.title"></a>
                                        </template>
                                        <template x-if="! item.url">
                                            <span class="font-semibold text-gw-primary" x-text="item.title"></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" x-text="item.subtitle ?? '—'"></td>
                                    <td class="px-4 py-3 text-slate-500" x-text="item.meta ?? '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
</div>
