<?php

namespace App\Services;

use App\Models\User;
use App\Services\Goals\GoalDashboardService;
use App\Services\Members\MemberInvitationService;
use App\Services\Teams\CreateMyTeamService;
use App\Support\ResourceLinkCategories;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class DashboardHomeService
{
    public function __construct(
        private readonly DailyQuoteService $dailyQuotes,
        private readonly ProfileCompletionService $profileCompletion,
        private readonly DashboardStatsService $stats,
        private readonly GoalDashboardService $goals,
        private readonly ResourceLinksService $resourceLinks,
        private readonly CreateMyTeamService $createMyTeam,
        private readonly MemberInvitationService $invitations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $user->loadMissing(['profile', 'rank', 'team']);

        return [
            'welcome' => $this->welcomeFor($user),
            'daily_quote' => $this->dailyQuotes->forDate(),
            'profile_completion' => $this->profileCompletion->snapshot($user),
            'progress' => $this->progressTrackersFor($user),
            'quick_actions' => $this->quickActionsFor($user),
            'quick_links' => $this->quickLinksFor(),
        ];
    }

    /**
     * @return array{greeting: string, headline: string, message: string, rank: string|null, team: string|null}
     */
    private function welcomeFor(User $user): array
    {
        $firstName = str($user->name)->before(' ')->toString() ?: $user->name;
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        $rankLabel = $user->rank?->code ?? 'New Recruit';
        $teamLabel = $user->team?->name;

        $message = filled($teamLabel)
            ? "Welcome back to GWTracks. You're on {$teamLabel} as {$rankLabel} — here's your command center for today."
            : "Welcome back to GWTracks. You're currently ranked {$rankLabel} — here's your command center for today.";

        return [
            'greeting' => $greeting,
            'headline' => "{$greeting}, {$firstName}.",
            'message' => $message,
            'rank' => $rankLabel,
            'team' => $teamLabel,
        ];
    }

    /**
     * @return list<array{key: string, label: string, percent: int, summary: string, route: string|null}>
     */
    private function progressTrackersFor(User $user): array
    {
        $goalSummary = $user->can('manage goals')
            ? $this->goals->summaryFor($user)
            : ['active' => 0, 'completion_percent' => 0];

        $trackers = [
            [
                'key' => 'onboarding',
                'label' => 'Onboarding Progress',
                'percent' => $this->stats->onboardingPercent($user),
                'summary' => 'New member onboarding checklist',
                'route' => Route::has('onboarding.index') ? 'onboarding.index' : null,
                'restricted' => false,
            ],
            [
                'key' => 'licensing',
                'label' => 'Licensing Progress',
                'percent' => $this->stats->licensingPercent($user),
                'summary' => 'Licensing milestone completion',
                'route' => Route::has('licensing.index') ? 'licensing.index' : null,
                'restricted' => false,
            ],
            [
                'key' => 'fap',
                'label' => 'FAP Progress',
                'percent' => $this->stats->apprenticeshipPercent($user),
                'summary' => 'Field apprenticeship program milestones',
                'route' => Route::has('apprenticeship.index') ? 'apprenticeship.index' : null,
                'restricted' => false,
            ],
            [
                'key' => 'training',
                'label' => 'Training Progress',
                'percent' => $this->stats->trainingPercent($user),
                'summary' => 'CFM training and academy lessons',
                'route' => Route::has('training.index') ? 'training.index' : null,
                'restricted' => false,
            ],
            [
                'key' => 'goals',
                'label' => 'Goal Progress',
                'percent' => (int) ($goalSummary['completion_percent'] ?? 0),
                'summary' => $user->can('manage goals')
                    ? (($goalSummary['active'] ?? 0).' active goal'.(($goalSummary['active'] ?? 0) === 1 ? '' : 's'))
                    : 'Goal planning is not enabled for your account',
                'route' => $user->can('manage goals') && Route::has('goals.index') ? 'goals.index' : null,
                'restricted' => ! $user->can('manage goals'),
            ],
        ];

        return $trackers;
    }

    /**
     * @return list<array{label: string, description: string, route: string, icon: string}>
     */
    private function quickActionsFor(User $user): array
    {
        $definitions = [
            [
                'label' => 'My Tasks',
                'description' => 'Review open tasks and priorities.',
                'action' => 'open-my-tasks-modal',
                'icon' => 'tasks',
            ],
            [
                'label' => 'Invite New Member',
                'description' => 'Generate a registration link and invite someone onto your team.',
                'action' => 'open-invite-member-modal',
                'icon' => 'invite',
                'visible' => fn (User $actor): bool => $this->invitations->canCreateInvitation($actor),
            ],
            [
                'label' => 'Create My Team',
                'description' => 'Name your team and move your recruit hierarchy onto it.',
                'action' => 'open-create-my-team-modal',
                'icon' => 'team',
                'visible' => fn (User $actor): bool => $this->createMyTeam->userMayCreate($actor),
            ],
            [
                'label' => 'Prospects',
                'description' => 'Manage your CRM pipeline.',
                'action' => 'open-dashboard-prospects-modal',
                'permissions' => ['manage prospects'],
                'icon' => 'prospects',
            ],
            [
                'label' => 'Prospect Activity',
                'description' => 'Log a touchpoint or schedule a future activity for a prospect.',
                'action' => 'open-dashboard-prospect-activity-modal',
                'permissions' => ['manage prospects'],
                'icon' => 'prospect_activity',
            ],
            [
                'label' => 'Schedule Phone Call',
                'description' => 'Plan a call reminder for prospects, clients, team, or others.',
                'action' => 'open-dashboard-schedule-phone-call-modal',
                'icon' => 'schedule_phone_call',
            ],
            [
                'label' => 'Training',
                'description' => 'Continue lessons and certifications.',
                'route' => 'training.index',
                'icon' => 'training',
            ],
            [
                'label' => 'Messages',
                'description' => 'Open recent conversations.',
                'route' => 'messages.index',
                'permissions' => ['view conversations'],
                'icon' => 'messages',
            ],
            [
                'label' => 'Goals',
                'description' => 'Track active goals and deadlines.',
                'route' => 'goals.index',
                'permissions' => ['manage goals'],
                'icon' => 'goals',
            ],
            [
                'label' => 'Calendar',
                'description' => 'View upcoming events.',
                'route' => 'calendar.index',
                'permissions' => ['view calendar'],
                'icon' => 'calendar',
            ],
            [
                'label' => 'Book Session',
                'description' => 'Schedule mentoring appointments.',
                'route' => 'bookings.my',
                'permissions' => ['view own bookings'],
                'icon' => 'bookings',
            ],
            [
                'label' => 'Resources',
                'description' => 'Browse documents and videos.',
                'route' => 'resources',
                'icon' => 'resources',
            ],
        ];

        return collect($definitions)
            ->filter(function (array $action) use ($user): bool {
                if (isset($action['route']) && ! Route::has($action['route'])) {
                    return false;
                }

                if (! isset($action['route']) && ! isset($action['action'])) {
                    return false;
                }

                foreach ($action['permissions'] ?? [] as $permission) {
                    if (! $user->can($permission)) {
                        return false;
                    }
                }

                if (isset($action['visible']) && is_callable($action['visible']) && ! $action['visible']($user)) {
                    return false;
                }

                return true;
            })
            ->map(fn (array $action): array => [
                'label' => $action['label'],
                'description' => $action['description'],
                'route' => isset($action['route']) ? route($action['route']) : null,
                'action' => $action['action'] ?? null,
                'icon' => $action['icon'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{links: Collection, categories: array<string, array{label: string, description: string, accent: string, icon: string}>, library_url: string|null}
     */
    private function quickLinksFor(): array
    {
        $links = $this->resourceLinks->dashboardLinks();

        return [
            'links' => $links,
            'categories' => ResourceLinkCategories::all(),
            'library_url' => Route::has('resources.links') ? route('resources.links') : null,
        ];
    }
}
