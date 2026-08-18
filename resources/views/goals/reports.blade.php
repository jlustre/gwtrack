<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent hover:underline">&larr; Back to Goals</a>
                    <h1 class="mt-3 text-3xl font-semibold">Goal Reports</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Download or email PDF summaries of your goal progress by reporting period.
                    </p>
                </div>
            </div>
        </div>

        @if (session('goals_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('goals_status') }}
            </div>
        @endif

        <livewire:goals.goal-reports />
    </div>
</x-app-layout>
