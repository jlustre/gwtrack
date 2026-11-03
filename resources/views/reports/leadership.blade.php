<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <a href="{{ route('team.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; My Team</a>
            <h1 class="mt-2 text-3xl font-semibold">Leadership Reports</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                Review onboarding, licensing, apprenticeship, mentor activity, training, and rank progress for the members you are authorized to see.
            </p>
        </div>

        <livewire:reports.leadership-reports />
    </div>
</x-app-layout>
