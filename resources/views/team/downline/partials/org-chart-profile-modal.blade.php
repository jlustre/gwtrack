<div
    x-show="profileModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="org-chart-profile-modal-title"
    @keydown.escape.window="closeProfile()"
>
    <div
        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl"
        x-on:click.outside="closeProfile()"
        x-show="selectedProfile"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Member Profile</p>
                <h2 id="org-chart-profile-modal-title" class="text-xl font-semibold text-gw-primary" x-text="selectedProfile?.name"></h2>
            </div>
            <button
                type="button"
                class="gw-icon-btn-close"
                x-on:click="closeProfile()"
                aria-label="Close profile"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-6 p-6" x-show="selectedProfile">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <template x-if="selectedProfile?.profile_photo_url">
                    <img
                        :src="selectedProfile.profile_photo_url"
                        :alt="selectedProfile.name + ' profile photo'"
                        class="h-20 w-20 shrink-0 rounded-full border border-gw-accent object-cover"
                    >
                </template>
                <template x-if="! selectedProfile?.profile_photo_url">
                    <span
                        class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-gw-accent bg-gw-primary text-2xl font-bold text-gw-accent"
                        x-text="selectedProfile?.avatar"
                    ></span>
                </template>
                <div class="min-w-0 flex-1 space-y-2">
                    <p class="text-sm text-slate-600" x-text="selectedProfile?.email"></p>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-1 text-xs font-bold text-gw-primary" x-text="selectedProfile?.rank"></span>
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="selectedProfile?.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                            x-text="selectedProfile?.status"
                        ></span>
                    </div>
                    <p class="text-sm text-slate-600">
                        <span class="font-semibold text-gw-primary" x-text="selectedProfile?.rank_name"></span>
                        <span class="text-slate-400"> · </span>
                        <span x-text="selectedProfile?.role_label"></span>
                    </p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-bold uppercase text-slate-500">Direct Recruits</div>
                    <div class="mt-1 text-xl font-semibold text-gw-primary" x-text="selectedProfile?.metrics?.direct_recruits ?? 0"></div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-bold uppercase text-slate-500">Total Downline</div>
                    <div class="mt-1 text-xl font-semibold text-gw-primary" x-text="selectedProfile?.metrics?.total_downline ?? 0"></div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-bold uppercase text-slate-500">Active Branch</div>
                    <div class="mt-1 text-xl font-semibold text-gw-primary" x-text="selectedProfile?.active_associates ?? 0"></div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-bold uppercase text-slate-500">Licensed</div>
                    <div class="mt-1 text-xl font-semibold text-gw-primary" x-text="selectedProfile?.licensed_associates ?? 0"></div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Progress Summary</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <template x-for="item in [
                        { label: 'Licensing', key: 'licensing' },
                        { label: 'Onboarding', key: 'onboarding' },
                        { label: 'Training', key: 'training' },
                        { label: 'Field Apprenticeship', key: 'apprenticeship' },
                        { label: 'Rank Advancement', key: 'rank' },
                    ]" :key="'progress-' + item.key">
                        <div>
                            <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <span x-text="item.label"></span>
                                <span class="text-gw-primary" x-text="progressLabel(selectedProfile?.progress?.[item.key])"></span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                                <div
                                    class="h-full rounded-full bg-gw-accent"
                                    :style="'width:' + progressWidth(selectedProfile?.progress?.[item.key]) + '%'"
                                ></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Member Details</h3>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-slate-500">Sponsor</dt><dd class="text-gw-primary" x-text="selectedProfile?.sponsor"></dd></div>
                    <div><dt class="font-bold text-slate-500">CFM / Mentor</dt><dd class="text-gw-primary" x-text="selectedProfile?.mentor"></dd></div>
                    <div><dt class="font-bold text-slate-500">Team</dt><dd class="text-gw-primary" x-text="selectedProfile?.team"></dd></div>
                    <div><dt class="font-bold text-slate-500">Country</dt><dd class="text-gw-primary" x-text="selectedProfile?.country"></dd></div>
                    <div><dt class="font-bold text-slate-500">City</dt><dd class="text-gw-primary" x-text="selectedProfile?.city"></dd></div>
                    <div><dt class="font-bold text-slate-500">Province / State</dt><dd class="text-gw-primary" x-text="selectedProfile?.province"></dd></div>
                    <div><dt class="font-bold text-slate-500">Timezone</dt><dd class="text-gw-primary" x-text="selectedProfile?.timezone"></dd></div>
                    <div><dt class="font-bold text-slate-500">Joined</dt><dd class="text-gw-primary" x-text="selectedProfile?.joined_at"></dd></div>
                    <div><dt class="font-bold text-slate-500">Last Activity</dt><dd class="text-gw-primary" x-text="selectedProfile?.last_activity"></dd></div>
                    <div><dt class="font-bold text-slate-500">Pending Licensing</dt><dd class="text-gw-primary" x-text="selectedProfile?.pending_licensing ?? 0"></dd></div>
                    <template x-if="selectedProfile?.can_see_sensitive">
                        <div><dt class="font-bold text-slate-500">Phone</dt><dd class="text-gw-primary" x-text="selectedProfile?.phone"></dd></div>
                    </template>
                    <template x-if="selectedProfile?.can_see_sensitive">
                        <div><dt class="font-bold text-slate-500">License</dt><dd class="text-gw-primary" x-text="selectedProfile?.license_number"></dd></div>
                    </template>
                </dl>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <a
                    :href="selectedProfile?.member_url"
                    class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-gw-primary-hover"
                >
                    Full Profile Page
                </a>
                <a
                    :href="selectedProfile?.org_chart_url"
                    class="rounded-lg border border-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted"
                >
                    Open Branch
                </a>
                <a
                    :href="selectedProfile?.tree_url"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Genealogy
                </a>
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    x-on:click="closeProfile()"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
