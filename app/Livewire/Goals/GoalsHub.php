<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class GoalsHub extends Component
{
    public const TAB_OVERVIEW = 'overview';

    public const TAB_PLAN = 'plan';

    public const TAB_CREATE = 'create';

    public const TAB_SCORECARD = 'scorecard';

    public const TAB_WHAT_IF = 'what-if';

    public const TAB_REPORTS = 'reports';

    public const TAB_SETTINGS = 'settings';

    public const TAB_TEAM = 'team';

    public const TAB_COACHING = 'coaching';

    #[Url(except: self::TAB_OVERVIEW)]
    public string $tab = self::TAB_OVERVIEW;

    public int $overviewKey = 0;

    public int $createKey = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);

        if (in_array($this->tab, [self::TAB_TEAM, self::TAB_COACHING], true) && ! $this->canAccessTab($this->tab)) {
            abort(403);
        }

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        if (! $this->canAccessTab($tab)) {
            return;
        }

        $this->tab = $tab;

        if ($tab === self::TAB_CREATE) {
            $this->createKey++;
        }

        if ($tab === self::TAB_OVERVIEW) {
            $this->overviewKey++;
        }
    }

    #[On('goals-hub-set-tab')]
    public function onSetTab(string $tab): void
    {
        $this->setTab($tab);
    }

    #[On('goals-goal-created')]
    public function onGoalCreated(): void
    {
        $this->overviewKey++;
        $this->tab = self::TAB_OVERVIEW;
    }

    public function render(): View
    {
        return view('livewire.goals.goals-hub', [
            'tabs' => $this->availableTabs(),
            'statusMessage' => session('goals_status'),
        ])->layout('layouts.app');
    }

    /**
     * @return list<array{key: string, label: string, tooltip: string, variant: string}>
     */
    private function availableTabs(): array
    {
        $tabs = [
            [
                'key' => self::TAB_OVERVIEW,
                'label' => 'Overview',
                'tooltip' => 'Dashboard, insights, and your My Goals list.',
                'variant' => 'secondary',
            ],
            [
                'key' => self::TAB_PLAN,
                'label' => 'Performance Planner',
                'tooltip' => 'Build a Success Blueprint. Reverse-engineer income, production, recruiting, or rank targets into linked daily activity goals.',
                'variant' => 'primary',
            ],
            [
                'key' => self::TAB_CREATE,
                'label' => 'Quick Goal',
                'tooltip' => 'Create one SMART goal step-by-step. Best for a single target without building a full performance plan.',
                'variant' => 'secondary',
            ],
            [
                'key' => self::TAB_SCORECARD,
                'label' => 'Scorecard',
                'tooltip' => 'Track prospecting, FNAs, presentations, and other activities against your targets by day, week, or month.',
                'variant' => 'secondary',
            ],
            [
                'key' => self::TAB_WHAT_IF,
                'label' => 'What-If',
                'tooltip' => 'Simulate a target and preview required funnel activities before you commit to a plan.',
                'variant' => 'secondary',
            ],
            [
                'key' => self::TAB_REPORTS,
                'label' => 'Reports',
                'tooltip' => 'Download or email PDF performance summaries for the past week, month, quarter, or year.',
                'variant' => 'secondary',
            ],
            [
                'key' => self::TAB_SETTINGS,
                'label' => 'Settings',
                'tooltip' => 'Configure commission rate, average premium, working calendar, and funnel conversion rates used in planning calculations.',
                'variant' => 'secondary',
            ],
        ];

        if (auth()->user()?->can('view team goals')) {
            $tabs[] = [
                'key' => self::TAB_TEAM,
                'label' => 'Team Goals',
                'tooltip' => 'View goal progress, off-track items, and member rollups across your team or downline.',
                'variant' => 'secondary',
            ];
        }

        if (auth()->user()?->can('coach goals')) {
            $tabs[] = [
                'key' => self::TAB_COACHING,
                'label' => 'CFM Coaching',
                'tooltip' => 'Review trainee goals, leave coach notes, and monitor pace alerts and conversion KPIs.',
                'variant' => 'secondary',
            ];
        }

        return $tabs;
    }

    private function normalizeTab(): void
    {
        if (! $this->canAccessTab($this->tab)) {
            $this->tab = self::TAB_OVERVIEW;
        }
    }

    private function canAccessTab(string $tab): bool
    {
        return match ($tab) {
            self::TAB_OVERVIEW,
            self::TAB_PLAN,
            self::TAB_CREATE,
            self::TAB_SCORECARD,
            self::TAB_WHAT_IF,
            self::TAB_REPORTS,
            self::TAB_SETTINGS => true,
            self::TAB_TEAM => (bool) auth()->user()?->can('view team goals'),
            self::TAB_COACHING => (bool) auth()->user()?->can('coach goals'),
            default => false,
        };
    }
}
