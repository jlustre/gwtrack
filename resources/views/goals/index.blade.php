<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Performance Management</p>
                    <h1 class="mt-2 text-3xl font-semibold">Goals & Performance</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Reverse-engineer income, production, and recruiting targets into daily activities. Track KPIs, forecasts, and coaching accountability.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 pb-1">
                    <x-goals.header-nav-link
                        :href="route('goals.plan')"
                        label="Performance Planner"
                        variant="primary"
                        tooltip="Build a Success Blueprint. Reverse-engineer income, production, recruiting, or rank targets into linked daily activity goals."
                    />
                    <x-goals.header-nav-link
                        :href="route('goals.create')"
                        label="Quick Goal"
                        tooltip="Create one SMART goal step-by-step. Best for a single target without building a full performance plan."
                    />
                    <x-goals.header-nav-link
                        :href="route('goals.scorecard')"
                        label="Scorecard"
                        tooltip="Track prospecting, FNAs, presentations, and other activities against your targets by day, week, or month."
                    />
                    <x-goals.header-nav-link
                        :href="route('goals.what-if')"
                        label="What-If"
                        tooltip="Simulate a target and preview required funnel activities before you commit to a plan."
                    />
                    <x-goals.header-nav-link
                        :href="route('goals.reports')"
                        label="Reports"
                        tooltip="Download or email PDF performance summaries for the past week, month, quarter, or year."
                    />
                    <x-goals.header-nav-link
                        :href="route('goals.settings')"
                        label="Settings"
                        tooltip="Configure commission rate, average premium, working calendar, and funnel conversion rates used in planning calculations."
                    />
                    @can('view team goals')
                        <x-goals.header-nav-link
                            :href="route('goals.team')"
                            label="Team Goals"
                            tooltip="View goal progress, off-track items, and member rollups across your team or downline."
                        />
                    @endcan
                    @can('coach goals')
                        <x-goals.header-nav-link
                            :href="route('goals.coaching')"
                            label="CFM Coaching"
                            tooltip="Review trainee goals, leave coach notes, and monitor pace alerts and conversion KPIs."
                        />
                    @endcan
                </div>
            </div>
        </div>

        @if (session('goals_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('goals_status') }}
            </div>
        @endif

        <livewire:goals.goal-performance-insights />

        <livewire:goals.goal-dashboard />

        <livewire:goals.goal-index />
    </div>
</x-app-layout>
