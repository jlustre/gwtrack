<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gw-primary">Profile</h3>
        @if ($portal['canEditProfile'])
            <button
                type="button"
                @click="showEditProfileModal = true"
                class="inline-flex items-center rounded-lg border border-gw-accent/40 px-3 py-1.5 text-xs font-semibold text-gw-accent-dark transition hover:bg-gw-accent-soft"
            >
                Edit
            </button>
        @endif
    </div>

    <div class="mb-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
        <div><span class="text-slate-500">Email:</span> <span class="text-gw-primary">{{ $profile['email'] }}</span></div>
        <div><span class="text-slate-500">Phone:</span> <span class="text-gw-primary">{{ $profile['phone'] }}</span></div>
        <div><span class="text-slate-500">Location:</span> <span class="text-gw-primary">{{ $profile['city'] }}, {{ $profile['province'] }}, {{ $profile['country'] }}</span></div>
        <div><span class="text-slate-500">Timezone:</span> <span class="text-gw-primary">{{ $profile['timezone'] }}</span></div>
        <div><span class="text-slate-500">Agency Owner:</span> <span class="text-gw-primary">{{ $profile['agencyOwner'] }}</span></div>
        <div><span class="text-slate-500">Last Activity:</span> <span class="text-gw-primary">{{ $profile['lastActivity'] }}</span></div>
        <div class="sm:col-span-2"><span class="text-slate-500">Languages:</span> <span class="text-gw-primary">{{ implode(', ', $profile['languages'] ?: ['—']) }}</span></div>
        <div class="sm:col-span-2"><span class="text-slate-500">Specialties:</span> <span class="text-gw-primary">{{ implode(', ', $profile['specialties'] ?: ['—']) }}</span></div>
        <div class="sm:col-span-2">
            <span class="text-slate-500">Licensed jurisdictions:</span>
            <span class="text-gw-primary">{{ $profile['licensedJurisdictionsLabel'] ?? '—' }}</span>
        </div>
    </div>

    @if ($profile['bio'])
        <p class="border-t border-slate-200 pt-4 text-sm leading-relaxed text-slate-600">{{ $profile['bio'] }}</p>
    @endif

    <div class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('cfm-training.index') }}" class="inline-flex items-center rounded-lg bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
            Open CFM Training
        </a>
        <a href="{{ route('calendar.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
            My Calendar
        </a>
        <a href="{{ route('bookings.dashboard') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-slate-50">
            Mentor Scheduling
        </a>
    </div>
</div>
