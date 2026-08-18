<div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-tracker-stat-card label="Total CFMs" theme="navy" subtitle="Across accessible hierarchies">
            <span x-text="stats.total"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="Available CFMs" theme="emerald" subtitle="0–2 apprentices">
            <span x-text="stats.available"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="Busy / Overloaded" theme="amber" subtitle="6+ apprentices">
            <span x-text="busyOverloadedCount"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="My Hierarchy" theme="gold" subtitle="Full access">
            <span x-text="stats.myHierarchy"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="External Hierarchy" theme="cyan" subtitle="May require approval">
            <span x-text="stats.externalHierarchy"></span>
        </x-tracker-stat-card>
    </div>

    <div class="grid gap-3 border-t border-slate-200/80 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-tracker-stat-card label="Active Apprentices" theme="navy" subtitle="Currently in FAP">
            <span x-text="stats.activeApprentices"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="Pending FAP Assignments" theme="amber" subtitle="Awaiting assignment">
            <span x-text="stats.pendingFap"></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="Avg Mentor Load" theme="slate" subtitle="Per CFM capacity">
            <span><span x-text="stats.averageLoad"></span><span class="text-sm font-normal text-slate-500">/6</span></span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="FAP Completion Rate" theme="emerald" subtitle="Graduates vs assigned">
            <span><span x-text="stats.fapCompletionRate"></span>%</span>
        </x-tracker-stat-card>
        <x-tracker-stat-card label="Avg Weekly Availability" theme="cyan" subtitle="Bookable hours">
            <span><span x-text="stats.avgWeeklyAvailabilityHours"></span>h</span>
        </x-tracker-stat-card>
    </div>
</div>
