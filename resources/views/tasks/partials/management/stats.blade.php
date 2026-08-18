<div class="grid gap-3 border-t border-slate-200/80 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    <x-tracker-stat-card label="Total Tasks" theme="gold" subtitle="All tasks in view">
        <span x-text="stats.total"></span>
    </x-tracker-stat-card>
    <x-tracker-stat-card label="Due Today" theme="amber" subtitle="Due by end of day">
        <span x-text="stats.dueToday"></span>
    </x-tracker-stat-card>
    <x-tracker-stat-card label="Overdue" theme="red" subtitle="Past due date">
        <span x-text="stats.overdue"></span>
    </x-tracker-stat-card>
    <x-tracker-stat-card label="Completed This Week" theme="emerald" subtitle="Finished this week">
        <span x-text="stats.completedWeek"></span>
    </x-tracker-stat-card>
    <x-tracker-stat-card label="High Priority" theme="violet" subtitle="Urgent or high priority">
        <span x-text="stats.highPriority"></span>
    </x-tracker-stat-card>
    <x-tracker-stat-card label="Assigned to Me" theme="navy" subtitle="Tasks you own">
        <span x-text="stats.assignedToMe"></span>
    </x-tracker-stat-card>
</div>
