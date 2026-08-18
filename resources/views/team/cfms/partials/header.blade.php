<div class="bg-gw-primary px-6 py-6 text-white">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Certified Field Mentor Management</p>
            <h1 class="mt-2 text-2xl font-semibold">Certified Field Mentors</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                Review mentor availability, workload, hierarchy source, apprenticeship capacity, and assignment readiness before assigning a new associate to the Field Apprenticeship Program.
            </p>
            <p class="mt-3 text-xs text-slate-300">{{ $todayLabel }} · {{ $user->roles->pluck('name')->first() ?? 'member' }} · {{ $user->team?->name ?? 'Unassigned' }}</p>
        </div>
        @include('team.cfms.partials.header-actions')
    </div>
</div>
