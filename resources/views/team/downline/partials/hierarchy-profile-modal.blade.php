<div
    x-show="profileModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="hierarchy-profile-modal-title"
    @keydown.escape.window="closeProfile()"
>
    <div
        class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl"
        x-on:click.outside="closeProfile()"
        x-show="selectedProfile"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Member Summary</p>
                <h2 id="hierarchy-profile-modal-title" class="text-xl font-semibold text-gw-primary" x-text="selectedProfile?.name"></h2>
            </div>
            <button
                type="button"
                class="gw-icon-btn-close"
                x-on:click="closeProfile()"
                aria-label="Close summary"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="space-y-5 p-6" x-show="selectedProfile">
            <div class="flex items-start gap-4">
                <template x-if="selectedProfile?.profile_photo_url">
                    <img
                        :src="selectedProfile.profile_photo_url"
                        :alt="selectedProfile.name + ' profile photo'"
                        class="h-16 w-16 shrink-0 rounded-full border border-gw-accent object-cover"
                    >
                </template>
                <template x-if="! selectedProfile?.profile_photo_url">
                    <span
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-gw-accent bg-gw-primary text-lg font-bold text-gw-accent"
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
                        <span class="text-xs text-slate-500" x-text="selectedProfile?.role_label"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                <div class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft p-3">
                    <div class="text-lg font-bold text-gw-accent-dark" x-text="selectedProfile?.metrics?.direct_recruits ?? 0"></div>
                    <div class="text-xs font-semibold text-gw-primary">Direct</div>
                </div>
                <div class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft p-3">
                    <div class="text-lg font-bold text-gw-accent-dark" x-text="selectedProfile?.metrics?.total_downline ?? 0"></div>
                    <div class="text-xs font-semibold text-gw-primary">Team</div>
                </div>
                <div class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft p-3">
                    <div class="text-lg font-bold text-gw-accent-dark" x-text="selectedProfile?.metrics?.prospects ?? 0"></div>
                    <div class="text-xs font-semibold text-gw-primary">Prospects</div>
                </div>
                <div class="rounded-lg border border-gw-accent/50 bg-gw-accent-soft p-3">
                    <div class="text-lg font-bold text-gw-accent-dark" x-text="selectedProfile?.metrics?.production_formatted ?? '$0'"></div>
                    <div class="text-xs font-semibold text-gw-primary">Production</div>
                </div>
            </div>

            <div class="grid gap-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-2 rounded-md bg-slate-50 px-3 py-2">
                    <span class="text-slate-500">Sponsor</span>
                    <span class="font-semibold text-gw-primary" x-text="selectedProfile?.sponsor"></span>
                </div>
                <div class="flex justify-between gap-2 rounded-md bg-slate-50 px-3 py-2">
                    <span class="text-slate-500">CFM</span>
                    <span class="font-semibold text-gw-primary" x-text="selectedProfile?.mentor"></span>
                </div>
                <div class="flex justify-between gap-2 rounded-md bg-slate-50 px-3 py-2 sm:col-span-2">
                    <span class="text-slate-500">Location</span>
                    <span class="font-semibold text-gw-primary" x-text="[selectedProfile?.city, selectedProfile?.province, selectedProfile?.country].filter(Boolean).join(', ')"></span>
                </div>
                <div class="flex justify-between gap-2 rounded-md bg-slate-50 px-3 py-2">
                    <span class="text-slate-500">Joined</span>
                    <span class="font-semibold text-gw-primary" x-text="selectedProfile?.joined_at"></span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span>Onboarding</span>
                    <span class="text-gw-primary" x-text="progressLabel(selectedProfile?.progress?.onboarding)"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-gw-accent" :style="'width:' + progressWidth(selectedProfile?.progress?.onboarding) + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span>Licensing</span>
                    <span class="text-gw-primary" x-text="progressLabel(selectedProfile?.progress?.licensing)"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-gw-accent" :style="'width:' + progressWidth(selectedProfile?.progress?.licensing) + '%'"></div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <a
                    :href="selectedProfile?.member_url"
                    class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-gw-primary-hover"
                >
                    View Full Member Profile
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
