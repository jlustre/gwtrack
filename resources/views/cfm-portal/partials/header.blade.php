@php
    $profile = $portal['profile'];
@endphp

<div class="bg-gw-primary px-6 py-6 text-white">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Certified Field Mentor</p>
            <h1 class="mt-2 text-2xl font-semibold">{{ $profile['name'] }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                {{ $profile['rank'] }} · {{ $profile['rankName'] }} — manage trainees, training progress, calendar availability, and mentor profile details from your CFM portal.
            </p>
            <p class="mt-3 text-xs text-slate-300">
                {{ $todayLabel }} · {{ $profile['certificationStatus'] }} · {{ $profile['workloadStatus'] }}
                @if ($user ?? null)
                    · {{ $user->roles->pluck('name')->first() ?? 'member' }} · {{ $user->team?->name ?? 'Unassigned' }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($portal['isAdminView'] && count($portal['cfmOptions']) > 0)
                <form method="GET" action="{{ route('cfm.portal') }}" class="inline-flex items-center gap-2">
                    <label for="cfm-select" class="whitespace-nowrap text-xs font-semibold text-slate-300">Viewing CFM:</label>
                    <select
                        id="cfm-select"
                        name="cfm"
                        onchange="this.form.submit()"
                        class="min-w-[200px] rounded-md border border-white/20 bg-white/10 px-3 py-2 text-sm text-slate-100 focus:border-gw-accent focus:ring-gw-accent"
                    >
                        @foreach ($portal['cfmOptions'] as $option)
                            <option value="{{ $option['id'] }}" @selected($option['id'] === $portal['selectedCfmId'])>{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

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
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Profile
                </button>
            @endif
        </div>
    </div>
</div>

@if ($portal['isAdminView'])
    <div class="border-t border-slate-200 bg-sky-50 px-6 py-3 text-sm text-sky-800">
        Admin view — you are reviewing {{ $portal['cfmUser']->name }}'s CFM portal.
    </div>
@endif
