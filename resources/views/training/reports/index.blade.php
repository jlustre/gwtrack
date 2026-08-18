<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <h1 class="mt-2 text-3xl font-semibold">Training Reports & Analytics</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Track lessons, courses, certifications, and team learning activity across reporting periods.
                    </p>
                </div>
            </div>
        </div>

        @if (session('training_report_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('training_report_status') }}
            </div>
        @endif

        <livewire:training.training-reports />
    </div>
</x-app-layout>
