<div
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
    x-on:keydown.escape.window="close()"
>
    <div
        class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
        x-on:click.stop
    >
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Prospect Activities</p>
                <h3 class="mt-1 text-xl font-semibold" x-text="prospect?.name ?? 'Prospect'"></h3>
                <p class="mt-1 text-sm text-slate-300">Log calls, meetings, and follow-up touchpoints for this prospect.</p>
            </div>
            <button type="button" x-on:click="close()" class="text-2xl leading-none text-slate-300 transition hover:text-white">&times;</button>
        </div>

        <div class="grid min-h-0 flex-1 gap-0 lg:grid-cols-[1.1fr_.9fr]">
            <div class="min-h-0 overflow-y-auto border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Activity History</h4>
                    <button
                        type="button"
                        x-on:click="resetForm()"
                        class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary"
                        x-show="formMode === 'edit'"
                        x-cloak
                    >
                        + New activity
                    </button>
                </div>

                <div x-show="loading" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    Loading activities...
                </div>

                <div x-show="error && ! loading" x-cloak class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>

                <div x-show="! loading && activities.length === 0" x-cloak class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No activities logged yet. Add the first one using the form.
                </div>

                <div class="space-y-3" x-show="! loading && activities.length > 0">
                    <template x-for="activity in activities" :key="'activity-' + activity.id">
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-gw-accent-muted px-2.5 py-0.5 text-xs font-semibold text-gw-primary" x-text="activity.activity_type_label"></span>
                                        <span class="text-xs text-slate-500" x-text="activity.occurred_at_label"></span>
                                    </div>
                                    <p class="mt-2 font-semibold text-gw-primary" x-show="activity.subject" x-text="activity.subject"></p>
                                    <p class="mt-1 text-[11px] text-slate-500" x-show="activity.duration_minutes" x-text="activity.duration_minutes + ' min'"></p>
                                    <p class="mt-1 text-sm text-slate-600" x-show="activity.notes" x-text="activity.notes"></p>
                                    <p class="mt-2 text-xs text-slate-500" x-show="activity.pipeline_stage_name">
                                        <span class="font-semibold text-slate-600">Pipeline stage:</span>
                                        <span x-text="activity.pipeline_stage_name"></span>
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500" x-show="activity.outcome">
                                        <span class="font-semibold text-slate-600">Outcome:</span>
                                        <span x-text="activity.outcome"></span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500" x-show="activity.next_action">
                                        <span class="font-semibold text-slate-600">Next action:</span>
                                        <span x-text="activity.next_action"></span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500" x-show="activity.next_follow_up_at_label">
                                        <span class="font-semibold text-slate-600">Follow up:</span>
                                        <span x-text="activity.next_follow_up_at_label"></span>
                                    </p>
                                    <p class="mt-2 text-xs text-slate-400" x-show="activity.user_name" x-text="'Logged by ' + activity.user_name"></p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button
                                        type="button"
                                        x-show="activity.can_edit"
                                        x-on:click="editActivity(activity)"
                                        title="Edit activity"
                                        class="inline-flex p-1 text-slate-500 transition hover:text-gw-accent"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    </button>
                                    <button
                                        type="button"
                                        x-show="activity.can_delete"
                                        x-on:click="deleteActivity(activity)"
                                        title="Delete activity"
                                        class="inline-flex p-1 text-slate-500 transition hover:text-red-600"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="min-h-0 overflow-y-auto bg-slate-50 p-5">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500" x-text="formMode === 'edit' ? 'Edit Activity' : 'Log Activity'"></h4>

                <form class="mt-4 space-y-4" x-on:submit.prevent="saveActivity()">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Type</label>
                        <select x-model="form.activity_type" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <template x-for="(label, value) in activityTypes" :key="'type-' + value">
                                <option :value="value" x-text="label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pipeline Stage</label>
                        <select x-model="form.pipeline_stage_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <template x-for="stage in pipelineStages" :key="'stage-' + stage.id">
                                <option :value="String(stage.id)" x-text="stage.label"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Updates the prospect stage when this activity is saved.</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <input type="text" x-model="form.subject" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Brief summary">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">When</label>
                        <input type="datetime-local" x-model="form.occurred_at" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duration (minutes)</label>
                        <input type="number" min="1" max="600" x-model="form.duration_minutes" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Optional">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <textarea x-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="What happened?"></textarea>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outcome</label>
                        <input type="text" x-model="form.outcome" list="prospect-activity-outcome-suggestions" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Connected, left voicemail, booked appointment…">
                        <datalist id="prospect-activity-outcome-suggestions">
                            <template x-for="suggestion in outcomeSuggestions" :key="'outcome-' + suggestion">
                                <option :value="suggestion"></option>
                            </template>
                        </datalist>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Next Action</label>
                        <textarea x-model="form.next_action" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="What should happen next?"></textarea>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Next Follow-Up</label>
                        <input type="datetime-local" x-model="form.next_follow_up_at" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" x-on:click="close()" class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                            Close
                        </button>
                        <button
                            type="submit"
                            class="flex-1 rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
                            x-bind:disabled="saving"
                            x-text="saving ? 'Saving...' : (formMode === 'edit' ? 'Update Activity' : 'Add Activity')"
                        ></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
