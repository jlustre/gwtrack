<?php

namespace App\Services;

use App\Http\Controllers\TaskController;
use App\Models\CalendarEvent;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\Rank;
use App\Models\User;
use App\Services\Communication\CommunicationHubService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardOverviewService
{
    public function __construct(
        private readonly DashboardStatsService $stats,
        private readonly ProfileCompletionService $profileCompletion,
        private readonly TaskController $tasks,
        private readonly ChecklistService $checklists,
        private readonly CommunicationHubService $communicationHub,
    ) {}

    public function forUser(User $user): array
    {
        $user->loadMissing(['profile', 'rank', 'team', 'mentor', 'sponsor']);

        return [
            'onboarding' => $this->onboarding($user),
            'licensing' => $this->licensing($user),
            'fap' => $this->fap($user),
            'training' => $this->training($user),
            'communications' => $this->communications($user),
            'performance' => $this->performance($user),
            'career' => $this->career($user),
            'upcoming_events' => $this->upcomingEvents($user),
        ];
    }

    /**
     * @return array{
     *     started: bool,
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     next_steps: list<array{title: string, status: string}>,
     *     preview_items: list<array{title: string, status: string, is_completed: bool}>,
     *     has_more: bool,
     *     route: string|null
     * }
     */
    private function onboarding(User $user): array
    {
        $steps = Checklist::query()
            ->forTypeCode('onboarding')
            ->applicableToCountry($user->profile?->country)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        if (! $this->checklists->hasTypeStarted($user, 'onboarding')) {
            return [
                'started' => false,
                'percent' => 0,
                'completed' => 0,
                'total' => $steps->count(),
                'next_steps' => [],
                'preview_items' => $this->checklistPreviewItems($steps, collect()),
                'has_more' => $steps->count() > 5,
                'route' => 'onboarding.index',
            ];
        }

        return $this->checklistOverview(
            $user,
            $steps,
            $this->stats->onboardingPercent($user),
            'onboarding.index'
        );
    }

    /**
     * @return array{percent: int, completed: int, total: int, next_steps: list<array{title: string, status: string}>, route: string}
     */
    private function licensing(User $user): array
    {
        if (! $this->checklists->hasTypeStarted($user, 'licensing')) {
            return $this->inactiveChecklistOverview();
        }

        $steps = Checklist::query()
            ->forTypeCode('licensing')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        return $this->checklistOverview(
            $user,
            $steps,
            $this->stats->licensingPercent($user),
            'licensing.index'
        );
    }

    /**
     * @return array{percent: int, completed: int, total: int, next_steps: list<array{title: string, status: string}>, route: string}
     */
    private function fap(User $user): array
    {
        if (! $this->checklists->hasTypeStarted($user, 'fap')) {
            return $this->inactiveChecklistOverview();
        }

        $steps = Checklist::query()
            ->forTypeCode('fap')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        return $this->checklistOverview(
            $user,
            $steps,
            $this->stats->apprenticeshipPercent($user),
            'apprenticeship.index'
        );
    }

    /**
     * @return array{
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     assessments_count: int|null,
     *     next_steps: list<array{title: string, status: string}>,
     *     route: string,
     *     cfm_training: array{show: bool, percent: int, completed: int, total: int, route: string|null}|null
     * }
     */
    private function training(User $user): array
    {
        $lessonIds = $this->publishedTrainingLessonIds();

        $progress = $lessonIds === []
            ? collect()
            : DB::table('training_progress')
                ->where('user_id', $user->id)
                ->whereIn('training_lesson_id', $lessonIds)
                ->get(['training_lesson_id', 'status'])
                ->keyBy('training_lesson_id');

        $lessons = DB::table('training_lessons')
            ->join('training_modules', 'training_modules.id', '=', 'training_lessons.training_module_id')
            ->whereIn('training_lessons.id', $lessonIds)
            ->orderBy('training_modules.sort_order')
            ->orderBy('training_lessons.sort_order')
            ->get(['training_lessons.id', 'training_lessons.title']);

        $completed = $lessons->filter(
            fn (object $lesson): bool => ($progress->get($lesson->id)?->status ?? 'not_started') === 'completed'
        )->count();

        $total = $lessons->count();
        $percent = $this->stats->trainingPercent($user);

        $nextSteps = $lessons
            ->filter(fn (object $lesson): bool => ($progress->get($lesson->id)?->status ?? 'not_started') !== 'completed')
            ->take(3)
            ->map(fn (object $lesson): array => [
                'title' => $lesson->title,
                'status' => $this->statusLabel($progress->get($lesson->id)?->status),
            ])
            ->values()
            ->all();

        $assessmentsCount = null;
        if (Schema::hasTable('assessments')) {
            $assessmentsCount = (int) DB::table('assessments')
                ->whereNull('deleted_at')
                ->count();
        }

        $cfmTraining = null;
        if (! $this->stats->userIsCfm($user) && $this->checklists->hasTypeStarted($user, 'cfm-training')) {
            $cfmOverview = $this->cfmTrainingOverview($user);
            if ($cfmOverview['total'] > 0) {
                $cfmTraining = [
                    'show' => true,
                    'percent' => $cfmOverview['percent'],
                    'completed' => $cfmOverview['completed'],
                    'total' => $cfmOverview['total'],
                    'route' => 'cfm-training.index',
                ];
            }
        }

        return [
            'percent' => $percent,
            'completed' => $completed,
            'total' => $total,
            'assessments_count' => $assessmentsCount,
            'next_steps' => $nextSteps,
            'route' => 'training.index',
            'cfm_training' => $cfmTraining,
        ];
    }

    /**
     * @return array{
     *     mentor: array{name: string, email: string|null, assigned: bool},
     *     sponsor: array{name: string|null},
     *     team_name: string|null,
     *     open_tasks: int,
     *     tasks_route: string,
     *     announcements: list<array{title: string, meta: string, slug: string, is_unread: bool, priority: string}>,
     *     announcements_route: string,
     *     announcements_unread_count: int,
     *     announcements_featured: list<array{title: string, summary: string|null, slug: string, category: string|null, priority: string}>,
     *     announcements_pending_critical: list<array{title: string, slug: string, priority: string, summary: string|null}>,
     *     team_route: string
     * }
     */
    private function communications(User $user): array
    {
        $snapshot = $this->communicationHub->dashboardCommunicationsFor($user);

        return [
            'mentor' => [
                'name' => $user->mentor?->name ?? 'Unassigned',
                'email' => $user->mentor?->email,
                'assigned' => $user->mentor !== null,
            ],
            'sponsor' => [
                'name' => $user->sponsor?->name,
            ],
            'team_name' => $user->team?->name,
            'open_tasks' => $this->tasks->openTaskCountFor($user),
            'tasks_route' => 'tasks.index',
            'announcements' => $snapshot['announcements'],
            'announcements_route' => 'communications.index',
            'announcements_unread_count' => $snapshot['unread_count'],
            'announcements_featured' => $snapshot['featured'],
            'announcements_pending_critical' => $snapshot['pending_critical'],
            'team_route' => 'team.index',
        ];
    }

    /**
     * @return array{
     *     profile_percent: int,
     *     prospects: int,
     *     recruits: int,
     *     production: string,
     *     profile_route: string
     * }
     */
    private function performance(User $user): array
    {
        return [
            'profile_percent' => $this->profileCompletion->percent($user),
            'prospects' => $this->stats->prospectCount($user),
            'recruits' => $this->stats->recruitCount($user),
            'production' => '$'.number_format($this->stats->annualProductionTotal($user)),
            'profile_route' => 'profile.edit',
        ];
    }

    /**
     * @return array{
     *     current_rank: array{code: string|null, name: string|null},
     *     next_rank: array{code: string|null, name: string|null}|null,
     *     percent: int,
     *     requirements: list<array{title: string, status: string}>,
     *     route: string
     * }
     */
    private function career(User $user): array
    {
        $currentRank = $user->rank;

        $nextRank = null;
        if ($currentRank !== null) {
            $nextRank = Rank::query()
                ->where('is_active', true)
                ->where('sort_order', '>', $currentRank->sort_order)
                ->orderBy('sort_order')
                ->first();
        }

        if ($nextRank === null) {
            return [
                'current_rank' => [
                    'code' => $currentRank?->code,
                    'name' => $currentRank?->name,
                ],
                'next_rank' => null,
                'percent' => 100,
                'requirements' => [],
                'route' => 'rank-advancement.index',
            ];
        }

        $requirements = DB::table('rank_requirements')
            ->where('rank_id', $nextRank->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title']);

        $progress = DB::table('user_rank_progress')
            ->where('user_id', $user->id)
            ->whereIn('rank_requirement_id', $requirements->pluck('id'))
            ->get(['rank_requirement_id', 'status'])
            ->keyBy('rank_requirement_id');

        $completed = $requirements->filter(
            fn (object $requirement): bool => ($progress->get($requirement->id)?->status ?? 'not_started') === 'completed'
        )->count();

        $total = $requirements->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $requirementItems = $requirements
            ->take(4)
            ->map(fn (object $requirement): array => [
                'title' => $requirement->title,
                'status' => $this->statusLabel($progress->get($requirement->id)?->status),
            ])
            ->values()
            ->all();

        return [
            'current_rank' => [
                'code' => $currentRank?->code,
                'name' => $currentRank?->name,
            ],
            'next_rank' => [
                'code' => $nextRank->code,
                'name' => $nextRank->name,
            ],
            'percent' => $percent,
            'requirements' => $requirementItems,
            'route' => 'rank-advancement.index',
        ];
    }

    /**
     * @return array{items: list<array{title: string, date_label: string, time_label: string, url: string|null}>, route: string|null, note: string|null}
     */
    private function upcomingEvents(User $user): array
    {
        if (! $user->can('view calendar')) {
            return [
                'items' => [],
                'route' => null,
                'note' => 'Calendar access is not enabled for your account.',
            ];
        }

        $events = CalendarEvent::query()
            ->where('starts_at', '>=', now())
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($query) => $query->where('user_id', $user->id));
            })
            ->orderBy('starts_at')
            ->limit(3)
            ->get(['id', 'title', 'starts_at', 'is_all_day']);

        return [
            'items' => $events->map(fn (CalendarEvent $event): array => [
                'title' => $event->title,
                'date_label' => $event->starts_at->format('M d'),
                'time_label' => $event->is_all_day ? 'All day' : $event->starts_at->format('g:i A'),
                'url' => route('calendar.events.show', $event),
            ])->all(),
            'route' => 'events.index',
            'note' => null,
        ];
    }

    /**
     * @param  Collection<int, object>|iterable<int, object>  $steps
     * @return array{
     *     started: true,
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     next_steps: list<array{title: string, status: string}>,
     *     preview_items: list<array{title: string, status: string, is_completed: bool}>,
     *     has_more: bool,
     *     route: string
     * }
     */
    private function checklistOverview(
        User $user,
        iterable $steps,
        int $percent,
        string $route,
    ): array {
        $steps = collect($steps);
        $stepIds = $steps->pluck('id');

        $progress = $stepIds->isEmpty()
            ? collect()
            : ChecklistProgress::query()
                ->where('user_id', $user->id)
                ->memberProgress()
                ->whereIn('checklist_id', $stepIds)
                ->get(['checklist_id', 'status'])
                ->keyBy('checklist_id');

        $completed = $steps->filter(
            fn (object $step): bool => ($progress->get($step->id)?->status ?? 'not_started') === 'completed'
        )->count();

        $nextSteps = $steps
            ->filter(fn (object $step): bool => ($progress->get($step->id)?->status ?? 'not_started') !== 'completed')
            ->take(3)
            ->map(fn (object $step): array => [
                'title' => $step->title,
                'status' => $this->statusLabel($progress->get($step->id)?->status),
            ])
            ->values()
            ->all();

        return [
            'started' => true,
            'percent' => $percent,
            'completed' => $completed,
            'total' => $steps->count(),
            'next_steps' => $nextSteps,
            'preview_items' => $this->checklistPreviewItems($steps, $progress),
            'has_more' => $steps->count() > 5,
            'route' => $route,
        ];
    }

    /**
     * @param  Collection<int, object>  $steps
     * @param  Collection<int|string, ChecklistProgress|object>  $progress
     * @return list<array{title: string, status: string, is_completed: bool}>
     */
    private function checklistPreviewItems(Collection $steps, Collection $progress): array
    {
        return $steps
            ->take(5)
            ->map(fn (object $step): array => [
                'title' => $step->title,
                'status' => $this->statusLabel($progress->get($step->id)?->status),
                'is_completed' => ($progress->get($step->id)?->status ?? 'not_started') === 'completed',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{started: false, percent: int, completed: int, total: int, next_steps: list<array{title: string, status: string}>, route: null}
     */
    private function inactiveChecklistOverview(): array
    {
        return [
            'started' => false,
            'percent' => 0,
            'completed' => 0,
            'total' => 0,
            'next_steps' => [],
            'route' => null,
        ];
    }

    /**
     * @return array{percent: int, completed: int, total: int}
     */
    private function cfmTrainingOverview(User $user): array
    {
        $moduleIds = Checklist::query()
            ->forTypeCode('cfm-training')
            ->where('is_active', true)
            ->pluck('id');

        if ($moduleIds->isEmpty()) {
            return ['percent' => 0, 'completed' => 0, 'total' => 0];
        }

        $completed = ChecklistProgress::query()
            ->where('user_id', $user->id)
            ->memberProgress()
            ->whereIn('checklist_id', $moduleIds)
            ->completed()
            ->count();

        $total = $moduleIds->count();

        return [
            'percent' => (int) round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
        ];
    }

    /**
     * @return list<int>
     */
    private function publishedTrainingLessonIds(): array
    {
        return DB::table('training_lessons')
            ->join('training_modules', 'training_modules.id', '=', 'training_lessons.training_module_id')
            ->join('training_categories', 'training_categories.id', '=', 'training_modules.training_category_id')
            ->where('training_modules.is_published', true)
            ->whereNull('training_modules.deleted_at')
            ->whereNull('training_lessons.deleted_at')
            ->whereNull('training_categories.deleted_at')
            ->pluck('training_lessons.id')
            ->all();
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'pending_confirmation' => 'Pending review',
            'rejected' => 'Needs revision',
            'ready_for_review' => 'Ready for review',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'in_progress' => 'In progress',
            'pending' => 'Pending',
            default => 'Not started',
        };
    }

    private function relativeTime(?string $timestamp): string
    {
        if ($timestamp === null) {
            return 'recently';
        }

        return \Carbon\Carbon::parse($timestamp)->diffForHumans();
    }
}
