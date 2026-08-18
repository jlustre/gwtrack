<div
    x-show="showTraineeChecklistModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center overflow-auto bg-slate-900/50 p-4 backdrop-blur-sm"
    @keydown.escape.window="closeTraineeChecklistModal()"
>
    <div
        class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-xl border border-slate-200 bg-white shadow-xl"
        @click.stop
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent-dark">Mentoring Checklist</p>
                <h3 class="mt-1 text-xl font-semibold text-gw-primary" x-text="checklistModalData?.trainee?.name || 'Trainee progress'"></h3>
                <p class="mt-1 text-sm text-slate-600" x-show="checklistModalData?.trainee?.rank">
                    Rank: <span x-text="checklistModalData?.trainee?.rank"></span>
                </p>
            </div>
            <button
                type="button"
                @click="closeTraineeChecklistModal()"
                class="shrink-0 text-2xl leading-none text-slate-400 hover:text-gw-primary"
                aria-label="Close checklist modal"
            >&times;</button>
        </div>

        <div class="border-b border-slate-200 px-6 py-4" x-show="checklistModalData && ! checklistModalLoading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Overall progress</span>
                        <span class="font-bold text-gw-accent-dark" x-text="`${checklistModalData?.stats?.percent ?? 0}%`"></span>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200">
                        <div
                            class="h-full rounded-full bg-gw-accent transition-all"
                            :style="`width: ${checklistModalData?.stats?.percent ?? 0}%`"
                        ></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        <span x-text="checklistModalData?.stats?.completed ?? 0"></span> accomplished ·
                        <span x-text="checklistModalData?.stats?.remaining ?? 0"></span> remaining ·
                        <span x-text="checklistModalData?.stats?.total ?? 0"></span> total
                    </p>
                </div>

                <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs font-semibold">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="checklistModalView === 'accomplished' ? 'bg-gw-accent text-gw-primary' : 'text-slate-500 hover:text-gw-primary'"
                        @click="checklistModalView = 'accomplished'"
                    >
                        Accomplished
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="checklistModalView === 'remaining' ? 'bg-slate-200 text-gw-primary' : 'text-slate-500 hover:text-gw-primary'"
                        @click="checklistModalView = 'remaining'"
                    >
                        Remaining
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <div x-show="checklistModalLoading" class="py-16 text-center">
                <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gw-accent border-t-transparent"></div>
                <p class="mt-4 text-sm text-slate-500">Loading checklist progress…</p>
            </div>

            <div x-show="checklistModalError && ! checklistModalLoading" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span x-text="checklistModalError"></span>
            </div>

            <template x-if="checklistModalData && ! checklistModalLoading && checklistModalView === 'accomplished'">
                <div class="space-y-4">
                    <template x-if="accomplishedPhases().length === 0">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-gw-primary">No items completed yet</p>
                            <p class="mt-2 text-sm text-slate-500">Start tracking mentoring from the full checklist page.</p>
                        </div>
                    </template>

                    <template x-for="phase in accomplishedPhases()" :key="`accomplished-phase-${phase.phase_number}`">
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-xs font-bold text-gw-accent-dark" x-text="`Phase ${phase.phase_number}`"></span>
                                        <h4 class="mt-1 text-sm font-semibold text-gw-primary" x-text="phase.phase_title"></h4>
                                        <p class="mt-0.5 text-xs text-slate-500" x-show="phase.phase_target" x-text="`Target: ${phase.phase_target}`"></p>
                                    </div>
                                    <span class="text-xs font-semibold text-emerald-700" x-text="`${phase.completed}/${phase.total}`"></span>
                                </div>
                            </div>

                            <template x-for="section in phase.sections" :key="`accomplished-section-${phase.phase_number}-${section.title}`">
                                <div class="border-b border-slate-200 last:border-b-0">
                                    <div class="bg-slate-50/80 px-4 py-2">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="section.title"></h5>
                                    </div>
                                    <ul class="divide-y divide-slate-200">
                                        <template x-for="item in section.items" :key="`accomplished-item-${item.id}`">
                                            <li class="flex items-start gap-3 px-4 py-3">
                                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                                        <path d="M20 6 9 17l-5-5" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-sm text-gw-primary" x-text="item.title"></p>
                                                    <p class="mt-1 text-xs text-slate-500" x-show="item.completed_at" x-text="`Completed ${item.completed_at}`"></p>
                                                    <p class="mt-1 text-xs text-slate-600" x-show="item.notes" x-text="item.notes"></p>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="checklistModalData && ! checklistModalLoading && checklistModalView === 'remaining'">
                <div class="space-y-4">
                    <template x-if="remainingPhases().length === 0">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-emerald-800">All checklist items are complete</p>
                            <p class="mt-2 text-sm text-slate-600">This trainee has finished every mentoring milestone.</p>
                        </div>
                    </template>

                    <template x-for="phase in remainingPhases()" :key="`remaining-phase-${phase.phase_number}`">
                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-xs font-bold text-slate-500" x-text="`Phase ${phase.phase_number}`"></span>
                                        <h4 class="mt-1 text-sm font-semibold text-gw-primary" x-text="phase.phase_title"></h4>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-500" x-text="`${phase.completed}/${phase.total}`"></span>
                                </div>
                            </div>

                            <template x-for="section in phase.sections" :key="`remaining-section-${phase.phase_number}-${section.title}`">
                                <div class="border-b border-slate-200 last:border-b-0">
                                    <div class="bg-slate-50/80 px-4 py-2">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="section.title"></h5>
                                    </div>
                                    <ul class="divide-y divide-slate-200">
                                        <template x-for="item in section.items" :key="`remaining-item-${item.id}`">
                                            <li class="flex items-start gap-3 px-4 py-3">
                                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 rounded-full border border-slate-300"></span>
                                                <p class="text-sm text-slate-700" x-text="item.title"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
            <p class="text-xs text-slate-500">Read-only summary. Use the full checklist page to update progress.</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="closeTraineeChecklistModal()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-slate-50"
                >
                    Close
                </button>
                <a
                    x-show="checklistModalData?.checklist_url"
                    :href="checklistModalData?.checklist_url"
                    class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary transition hover:bg-gw-accent-hover"
                >
                    Open full checklist
                </a>
            </div>
        </div>
    </div>
</div>
