<div x-show="showProfilePanel" x-cloak class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="showProfilePanel = false">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showProfilePanel = false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-2xl overflow-y-auto border-l border-slate-200 bg-white shadow-2xl">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white/95 p-4 backdrop-blur-sm">
            <h3 class="text-xl font-semibold text-gw-primary">CFM Profile</h3>
            <button type="button" @click="showProfilePanel = false" class="text-2xl leading-none text-slate-400 hover:text-gw-primary">&times;</button>
        </div>
        <div class="space-y-5 p-6" x-show="selectedCfm">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-gw-accent to-gw-accent-dark text-2xl font-bold text-gw-primary" x-text="selectedCfm?.initials"></div>
                <div>
                    <div class="text-xl font-bold text-gw-primary" x-text="selectedCfm?.name"></div>
                    <div class="text-gw-accent-dark" x-text="selectedCfm?.rank"></div>
                    <div class="mt-1 text-sm" :class="selectedCfm?.inMyHierarchy ? 'text-emerald-700' : 'text-sky-700'" x-text="selectedCfm?.hierarchy"></div>
                </div>
            </div>

            <p x-show="selectedCfm?.hierarchyNotice" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" x-text="selectedCfm?.hierarchyNotice"></p>
            <p x-show="selectedCfm?.limitedVisibility" class="text-xs text-slate-500">Limited visibility — apprentice details may be restricted for external CFMs.</p>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-slate-500">Location:</span> <span class="text-gw-primary" x-text="selectedCfm?.location"></span></div>
                <div><span class="text-slate-500">Timezone:</span> <span class="text-gw-primary" x-text="selectedCfm?.timezone"></span></div>
                <div><span class="text-slate-500">Agency Owner:</span> <span class="text-gw-primary" x-text="selectedCfm?.agencyOwner"></span></div>
                <div><span class="text-slate-500">Certification:</span> <span class="text-gw-primary" x-text="selectedCfm?.certificationStatus"></span></div>
                <div class="col-span-2"><span class="text-slate-500">Languages:</span> <span class="text-gw-primary" x-text="(selectedCfm?.languages || []).join(', ') || '—'"></span></div>
                <div class="col-span-2"><span class="text-slate-500">Specialty:</span> <span class="text-gw-primary" x-text="(selectedCfm?.specialties || []).join(', ') || '—'"></span></div>
                <div class="col-span-2">
                    <span class="text-slate-500">Licensed jurisdictions:</span>
                    <span class="text-gw-primary" x-text="selectedCfm?.licensedJurisdictionsLabel || '—'"></span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4" x-show="selectedCfm">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h4 class="text-sm font-semibold text-gw-accent-dark">Licensed provinces / states</h4>
                    <button
                        type="button"
                        class="text-xs font-semibold text-gw-accent-dark hover:text-gw-accent"
                        @click="showLicensedEdit ? closeLicensedEdit() : openLicensedEdit()"
                        x-text="showLicensedEdit ? 'Cancel' : 'Edit'"
                    ></button>
                </div>

                <div x-show="! showLicensedEdit" class="text-sm text-slate-600" x-text="selectedCfm?.licensedJurisdictionsLabel || 'None on file — add jurisdictions so apprentices can be matched by location.'"></div>

                <form
                    x-show="showLicensedEdit"
                    x-cloak
                    method="POST"
                    :action="licensedUpdateUrl"
                    class="space-y-3"
                    @submit="licensedSaving = true"
                >
                    @csrf
                    @method('PATCH')

                    @if ($cfmLicensedFeedback ?? null)
                        <div class="rounded-lg border px-4 py-3 text-sm {{ ($cfmLicensedFeedback['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' }}" role="alert">
                            <p class="font-semibold">{{ ($cfmLicensedFeedback['type'] ?? '') === 'success' ? 'Saved' : 'Could not save' }}</p>
                            <p class="mt-1">{{ $cfmLicensedFeedback['message'] }}</p>
                        </div>
                    @endif

                    @if ($errors->any() && ($openCfmLicensedEdit ?? false))
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-xs text-slate-500">Select every jurisdiction where this CFM holds a life insurance license.</p>

                    <div class="max-h-56 space-y-4 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 pr-1">
                        <template x-for="country in licensedCountries" :key="'lic-country-' + country">
                            <div>
                                <p class="mb-2 text-xs font-semibold text-gw-accent-dark" x-text="country"></p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <template x-for="entry in provincesForCountry(country)" :key="entry.key">
                                        <label class="flex cursor-pointer items-start gap-2 text-xs text-slate-700">
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-gw-accent focus:ring-gw-accent"
                                                :checked="licensedDraft.includes(entry.key)"
                                                @change="toggleLicensedJurisdiction(entry.key, $event.target.checked)"
                                            >
                                            <span x-text="entry.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-for="key in licensedDraft" :key="'lic-hidden-' + key">
                        <input type="hidden" name="licensed_jurisdictions[]" :value="key">
                    </template>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover disabled:opacity-60"
                        :disabled="licensedSaving"
                    >
                        <span x-show="! licensedSaving">Save licensed jurisdictions</span>
                        <span x-show="licensedSaving" x-cloak>Saving…</span>
                    </button>
                </form>
            </div>

            <p class="text-sm leading-relaxed text-slate-600" x-text="selectedCfm?.bio"></p>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="font-semibold text-gw-accent-dark">Workload &amp; Metrics</h4>
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-slate-600">
                    <span>Active: <strong class="text-gw-primary" x-text="selectedCfm?.activeApprentices + '/' + selectedCfm?.maxApprentices"></strong></span>
                    <span>Completion: <strong class="text-emerald-700" x-text="selectedCfm?.completionRate + '%'"></strong></span>
                    <span>Score: <strong class="text-gw-primary" x-text="selectedCfm?.score + '/100'"></strong></span>
                    <span class="rounded-full px-2 py-0.5 text-xs" :class="statusBadgeClass(selectedCfm?.statusColor)" x-text="selectedCfm?.recommendationBand"></span>
                </div>
                <div class="mt-3 h-2 w-full rounded-full bg-slate-200">
                    <div class="h-2 rounded-full bg-gw-accent" :style="selectedCfm ? 'width:' + loadWidth(selectedCfm) + '%' : 'width:0%'"></div>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-500">
                    <span>Calendar busyness: <span class="text-slate-700" x-text="selectedCfm?.calendarBusyness + '%'"></span></span>
                    <span>Overdue tasks: <span :class="selectedCfm?.overdueTasks > 0 ? 'text-red-700' : 'text-slate-700'" x-text="selectedCfm?.overdueTasks"></span></span>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4" x-show="selectedCfm?.calendarPreview">
                <h4 class="font-semibold text-gw-accent-dark">Calendar Preview</h4>
                <div class="mt-2 space-y-1 text-sm text-slate-600">
                    <p>Next available: <span class="font-medium text-gw-primary" x-text="selectedCfm?.nextAvailable"></span></p>
                    <p>Booked this week: <span x-text="selectedCfm?.calendarPreview?.bookedSessions"></span></p>
                    <p>Open slots this week: <span x-text="selectedCfm?.calendarPreview?.slotsThisWeek"></span></p>
                    <p>Upcoming sessions (7d): <span x-text="selectedCfm?.upcomingSessions"></span></p>
                    <p x-show="selectedCfm?.calendarPreview?.conflictWarning" class="font-medium text-red-700">Calendar conflict warning</p>
                    <template x-for="slot in selectedCfm?.nextSlots || []" :key="slot">
                        <p class="text-xs text-slate-500" x-text="'· ' + slot"></p>
                    </template>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="font-semibold text-gw-accent-dark">Apprentice Breakdown</h4>
                <dl class="mt-2 grid grid-cols-2 gap-2 text-xs" x-show="selectedCfm?.apprenticeBreakdown">
                    <template x-for="(val, key) in selectedCfm.apprenticeBreakdown" :key="key">
                        <div class="flex justify-between rounded-lg bg-slate-50 px-2 py-1.5">
                            <span class="capitalize text-slate-500" x-text="key.replace(/([A-Z])/g, ' $1')"></span>
                            <span class="font-medium text-gw-primary" x-text="val"></span>
                        </div>
                    </template>
                </dl>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="font-semibold text-gw-accent-dark">Active Apprentices</h4>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                    <template x-for="a in selectedCfm?.apprentices || []" :key="a.id">
                        <li><span class="font-medium text-gw-primary" x-text="a.name"></span> · <span class="text-slate-500" x-text="a.rank"></span> · <span class="text-xs" x-text="a.status"></span></li>
                    </template>
                    <li x-show="!(selectedCfm?.apprentices?.length)" class="text-slate-500">No apprentices assigned</li>
                </ul>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="font-semibold text-gw-accent-dark">Recent Activity</h4>
                <ul class="mt-2 space-y-1 text-xs text-slate-500">
                    <template x-for="(item, i) in selectedCfm?.activityTimeline || []" :key="'act-' + i">
                        <li><span class="text-slate-700" x-text="item.label"></span> · <span x-text="item.time"></span></li>
                    </template>
                </ul>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="font-semibold text-gw-accent-dark">Assignment History</h4>
                <ul class="mt-2 space-y-1 text-xs text-slate-500">
                    <template x-for="(h, i) in selectedCfm?.assignmentHistory || []" :key="'hist-' + i">
                        <li x-text="h.apprentice + ' — ' + h.status + ' (' + h.date + ')'"></li>
                    </template>
                </ul>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="openAssign(selectedCfm); showProfilePanel = false" class="flex-1 rounded-lg bg-gw-accent py-2.5 font-bold text-gw-primary transition hover:bg-gw-accent-hover">
                    Assign Associate
                </button>
                <a :href="selectedCfm?.profileUrl" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-center text-sm font-semibold text-gw-primary transition hover:bg-slate-50">Full Profile</a>
            </div>
        </div>
    </div>
</div>
