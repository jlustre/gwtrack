<div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-gw-primary via-[#102847] to-gw-primary shadow-lg">
    <div class="px-5 py-5 text-white sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gw-accent">CFM Portal</p>
                <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-slate-300">Certified Field Mentor</p>
                <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">{{ $profile['name'] }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    {{ $profile['rank'] }} · {{ $profile['rankName'] }} — command center for trainee coaching, FAP progress, licensing, and mentor operations.
                </p>
                <p class="mt-3 text-xs text-slate-400">
                    {{ $todayLabel }} · {{ $profile['certificationStatus'] }} · {{ $profile['workloadStatus'] }}
                    @if ($user ?? null)
                        · {{ $user->roles->pluck('name')->first() ?? 'member' }} · {{ $user->team?->name ?? 'Unassigned' }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($portal['isAdminView'] && count($portal['cfmOptions']) > 0)
                    <div class="inline-flex items-center gap-2">
                        <label for="cfm-select" class="whitespace-nowrap text-xs font-semibold text-slate-300">Viewing CFM:</label>
                        <select
                            id="cfm-select"
                            wire:change="$set('cfmUserId', $event.target.value ? parseInt($event.target.value, 10) : null)"
                            class="min-w-[200px] rounded-md border border-white/20 bg-white/10 px-3 py-2 text-sm text-slate-100 focus:border-gw-accent focus:ring-gw-accent"
                        >
                            @foreach ($portal['cfmOptions'] as $option)
                                <option value="{{ $option['id'] }}" @selected($option['id'] === $portal['selectedCfmId'])>{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="$set('sidebarOpen', true)"
                    class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15 xl:hidden"
                >
                    My Trainees
                </button>

                <a href="{{ route('cfm-training.index') }}" class="inline-flex items-center gap-1.5 rounded-md border border-gw-accent/50 px-3.5 py-2 text-sm font-medium text-gw-accent transition hover:bg-gw-accent/10">
                    CFM Training
                </a>
                <a href="{{ route('calendar.index') }}" class="inline-flex items-center gap-1.5 rounded-md border border-white/20 bg-white/10 px-3.5 py-2 text-sm text-slate-100 transition hover:border-gw-accent hover:bg-white/15">
                    My Calendar
                </a>

                @if ($portal['canEditProfile'])
                    <button
                        type="button"
                        @click="showEditProfileModal = true"
                        class="inline-flex items-center gap-1.5 rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover"
                    >
                        Edit Profile
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if ($portal['isAdminView'])
        <div class="border-t border-white/10 bg-sky-950/40 px-5 py-2.5 text-sm text-sky-100 sm:px-6">
            Admin view — you are reviewing {{ $portal['cfmUser']->name }}'s CFM portal.
        </div>
    @endif
</div>
