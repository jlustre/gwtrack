<template x-teleport="body">
    <div
        x-show="checklistModal !== null"
        x-cloak
        class="fixed inset-0 z-[200] overflow-y-auto px-4 py-8 sm:px-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="checklist-detail-title"
    >
        <div class="fixed inset-0 bg-slate-950/60" x-on:click="closeChecklist()" aria-hidden="true"></div>

        <div class="relative z-10 mx-auto w-full max-w-3xl rounded-lg bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gw-accent">Checklist Details</p>
                    <h3 id="checklist-detail-title" class="mt-0.5 text-base font-semibold text-gw-primary" x-text="activeSummary?.name ?? 'Checklist'"></h3>
                    <p class="mt-0.5 text-xs text-slate-600" x-show="activeSummary" x-text="`${activeSummary.completed} of ${activeSummary.total} complete (${activeSummary.percent}%)`"></p>
                </div>
                <button type="button" class="shrink-0 rounded-md p-1 text-slate-500 hover:bg-slate-100" x-on:click="closeChecklist()" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="max-h-[min(70vh,36rem)] overflow-y-auto px-4 py-3">
                <template x-if="activeSummary">
                    <div>
                        <div class="mb-3">
                            <div class="mb-1 flex items-center justify-between text-[11px] font-semibold text-slate-600">
                                <span>Progress</span>
                                <span class="text-gw-primary" x-text="`${activeSummary.percent}%`"></span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100">
                                <div class="h-1.5 rounded-full bg-gw-accent" :style="`width: ${activeSummary.percent}%`"></div>
                            </div>
                        </div>

                        <dl class="mb-3 grid gap-1.5 text-[11px] sm:grid-cols-3">
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                <dt class="font-semibold uppercase tracking-wide text-slate-500">Started</dt>
                                <dd class="mt-0.5 whitespace-nowrap tabular-nums text-slate-700" x-text="activeSummary.started_at ?? '—'"></dd>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                <dt class="font-semibold uppercase tracking-wide text-slate-500">Started by</dt>
                                <dd class="mt-0.5 truncate text-slate-700" x-text="activeSummary.started_by ?? '—'"></dd>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                <dt class="font-semibold uppercase tracking-wide text-slate-500">Due</dt>
                                <dd
                                    class="mt-0.5 whitespace-nowrap tabular-nums"
                                    :class="activeSummary?.due_at && activeSummary.is_due_overdue ? 'font-semibold text-red-600' : 'text-slate-700'"
                                    x-text="activeSummary.due_at ?? '—'"
                                ></dd>
                            </div>
                        </dl>

                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-[11px] leading-4">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col" class="px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Item</th>
                                        <th scope="col" class="px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Scope</th>
                                        <th scope="col" class="whitespace-nowrap px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Req.</th>
                                        <th scope="col" class="whitespace-nowrap px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Due</th>
                                        <th scope="col" class="whitespace-nowrap px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
                                        <th scope="col" class="whitespace-nowrap px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Submitted</th>
                                        <th scope="col" class="whitespace-nowrap px-2 py-1.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Completed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="row in activeSummary.items" :key="row.item + row.status + row.due_at">
                                        <tr class="hover:bg-gw-accent-soft/40">
                                            <td class="max-w-[12rem] px-2 py-1.5 text-gw-primary" x-text="row.item"></td>
                                            <td class="max-w-[6rem] truncate px-2 py-1.5 text-gw-primary" x-text="row.category"></td>
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gw-primary" x-text="row.required"></td>
                                            <td
                                                class="whitespace-nowrap px-2 py-1.5 tabular-nums"
                                                :class="row.due_at !== '—' && row.is_due_overdue ? 'font-semibold text-red-600' : 'text-slate-700'"
                                                x-text="row.due_at"
                                            ></td>
                                            <td class="whitespace-nowrap px-2 py-1.5">
                                                <span
                                                    class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-3"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-700': row.status_key === 'completed',
                                                        'bg-amber-100 text-amber-700': row.status_key === 'pending_confirmation',
                                                        'bg-red-100 text-red-700': row.status_key === 'rejected',
                                                        'bg-slate-100 text-slate-600': !['completed', 'pending_confirmation', 'rejected'].includes(row.status_key),
                                                    }"
                                                    x-text="row.status"
                                                ></span>
                                            </td>
                                            <td class="whitespace-nowrap px-2 py-1.5 tabular-nums text-gw-primary" x-text="row.submitted_at"></td>
                                            <td class="whitespace-nowrap px-2 py-1.5 tabular-nums text-gw-primary" x-text="row.completed_at"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-2">
                <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" x-on:click="closeChecklist()">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
