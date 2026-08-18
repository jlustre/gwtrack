@php
    $panelTitles = [
        'overview' => ['title' => 'Goals & Performance', 'subtitle' => 'Reverse-engineer income, production, and recruiting targets into daily activities. Track KPIs, forecasts, and coaching accountability.'],
        'plan' => ['title' => 'Performance Planner', 'subtitle' => 'Build a Success Blueprint by reverse-engineering your target into linked activity goals.'],
        'create' => ['title' => 'Create Goal', 'subtitle' => 'Nine-step SMART goal wizard with milestones and accountability.'],
        'scorecard' => ['title' => 'Activity Scorecard', 'subtitle' => 'Track daily, weekly, and monthly activity performance against your plan.'],
        'what-if' => ['title' => 'What-If Calculator', 'subtitle' => 'Simulate targets and instantly see required activities at every funnel stage.'],
        'reports' => ['title' => 'Goal Reports', 'subtitle' => 'Download or email PDF summaries of your goal progress by reporting period.'],
        'settings' => ['title' => 'Planning Settings', 'subtitle' => 'Configure commission rates, average premium, working calendar, and funnel conversion assumptions.'],
        'team' => ['title' => 'Team Goals', 'subtitle' => 'Monitor direct recruits and downline goal progress, and prioritize coaching conversations.'],
        'coaching' => ['title' => 'CFM Coaching', 'subtitle' => 'Trainee goals, coach notes, and AI-ready recommendations.'],
    ];
    $current = $panelTitles[$tab] ?? $panelTitles['overview'];
@endphp

<div class="space-y-6">
    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Performance Management</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ $current['title'] }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                    {{ $current['subtitle'] }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 pb-1">
                @foreach ($tabs as $navTab)
                    <x-goals.header-nav-link
                        :label="$navTab['label']"
                        :tooltip="$navTab['tooltip']"
                        :variant="$navTab['variant']"
                        :tab="$navTab['key']"
                        :active="$tab === $navTab['key']"
                    />
                @endforeach
            </div>
        </div>
    </div>

    @if ($statusMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" wire:key="goals-status-{{ md5($statusMessage) }}">
            {{ $statusMessage }}
        </div>
    @endif

    <div wire:key="goals-panel-{{ $tab }}-{{ $tab === 'overview' ? $overviewKey : ($tab === 'create' ? $createKey : 0) }}">
        @switch($tab)
            @case('plan')
                <livewire:goals.performance-planner-wizard wire:key="plan-panel" />
                @break

            @case('create')
                <livewire:goals.goal-wizard wire:key="create-panel-{{ $createKey }}" :template="request()->integer('template') ?: null" />
                @break

            @case('scorecard')
                <livewire:goals.activity-scorecard wire:key="scorecard-panel" />
                @break

            @case('what-if')
                <livewire:goals.what-if-calculator wire:key="what-if-panel" />
                @break

            @case('reports')
                <livewire:goals.goal-reports wire:key="reports-panel" />
                @break

            @case('settings')
                <livewire:goals.goal-planning-settings-panel wire:key="settings-panel" />
                @break

            @case('team')
                <livewire:goals.team-goals-panel wire:key="team-panel" />
                @break

            @case('coaching')
                <livewire:goals.cfm-coaching-goals wire:key="coaching-panel" />
                @break

            @default
                <div class="space-y-6">
                    <livewire:goals.goal-performance-insights wire:key="insights-{{ $overviewKey }}" />
                    <livewire:goals.goal-dashboard wire:key="dashboard-{{ $overviewKey }}" />
                    <livewire:goals.goal-index wire:key="index-{{ $overviewKey }}" />
                </div>
        @endswitch
    </div>
</div>
