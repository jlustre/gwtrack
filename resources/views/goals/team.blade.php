<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent hover:underline">&larr; Back to Goals</a>
                    <h1 class="mt-3 text-3xl font-semibold">Team Goals</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Monitor direct recruits and downline goal progress, spot off-track performance early, and prioritize coaching conversations.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('goals.index') }}" class="inline-flex items-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                        My Goals
                    </a>
                    @can('coach goals')
                        <a href="{{ route('goals.coaching') }}" class="inline-flex items-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                            CFM Coaching
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <livewire:goals.team-goals-panel />
    </div>
</x-app-layout>
