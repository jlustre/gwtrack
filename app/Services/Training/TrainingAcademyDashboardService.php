<?php

namespace App\Services\Training;

use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\User;
use App\Models\UserTrainingBadge;
use App\Models\UserTrainingCertification;
use App\Models\UserTrainingPathEnrollment;
use App\Services\ChecklistService;
use App\Services\DashboardStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingAcademyDashboardService
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly DashboardStatsService $dashboardStats,
        private readonly TrainingPathService $paths,
        private readonly TrainingGamificationService $gamification,
        private readonly TrainingRecommendationService $recommendations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardFor(User $user): array
    {
        $publishedLessonIds = DB::table('training_lessons')
            ->join('training_modules', 'training_modules.id', '=', 'training_lessons.training_module_id')
            ->where('training_modules.is_published', true)
            ->where('training_modules.status', 'published')
            ->whereNull('training_modules.deleted_at')
            ->whereNull('training_lessons.deleted_at')
            ->pluck('training_lessons.id');

        $progress = TrainingProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('training_lesson_id', $publishedLessonIds)
            ->get();

        $completedLessons = $progress->where('status', 'completed')->count();
        $inProgressLessons = $progress->where('status', 'in_progress')->count();
        $totalPublishedLessons = $publishedLessonIds->count();

        $assignedModules = TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $overdueAssignments = TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $certificationsEarned = UserTrainingCertification::query()
            ->where('user_id', $user->id)
            ->where('status', 'issued')
            ->count();

        $trainingHours = (int) round($progress->sum('time_spent_seconds') / 3600);

        $cards = [
            ['key' => 'assigned', 'label' => 'Courses Assigned', 'value' => $assignedModules, 'accent' => 'text-gw-primary'],
            ['key' => 'completed', 'label' => 'Courses Completed', 'value' => $this->completedModuleCount($user), 'accent' => 'text-emerald-700'],
            ['key' => 'certifications', 'label' => 'Certifications Earned', 'value' => $certificationsEarned, 'accent' => 'text-gw-accent-dark'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'value' => $inProgressLessons + $this->activePathCount($user), 'accent' => 'text-sky-700'],
            ['key' => 'overdue', 'label' => 'Overdue Training', 'value' => $overdueAssignments, 'accent' => 'text-red-700'],
            ['key' => 'hours', 'label' => 'Training Hours', 'value' => $trainingHours.'h', 'accent' => 'text-violet-700'],
            ['key' => 'fap', 'label' => 'FAP Completion', 'value' => $this->checklistPercentLabel($user, 'fap'), 'accent' => 'text-amber-800'],
            ['key' => 'licensing', 'label' => 'Licensing Progress', 'value' => $this->checklistPercentLabel($user, 'licensing'), 'accent' => 'text-blue-700'],
        ];

        return [
            'cards' => $cards,
            'lesson_completion_percent' => $totalPublishedLessons > 0
                ? (int) round(($completedLessons / $totalPublishedLessons) * 100)
                : 0,
            'monthly_activity' => $this->monthlyActivity($user),
            'learning_paths' => $this->learningPathsFor($user),
            'featured_courses' => $this->featuredCourses(),
            'recommendations' => $this->recommendations->recommendationRowsFor($user, 5),
            'recent_badges' => $this->recentBadges($user),
            'gamification' => $this->gamification->summaryFor($user),
            'checklist_links' => $this->checklistLinks(),
        ];
    }

    private function completedModuleCount(User $user): int
    {
        return TrainingModule::query()
            ->published()
            ->whereHas('lessons')
            ->whereDoesntHave('lessons', function ($query) use ($user): void {
                $query->whereDoesntHave('progress', function ($progressQuery) use ($user): void {
                    $progressQuery
                        ->where('user_id', $user->id)
                        ->where('status', 'completed');
                });
            })
            ->count();
    }

    private function activePathCount(User $user): int
    {
        return UserTrainingPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->count();
    }

    private function checklistPercentLabel(User $user, string $typeCode): string
    {
        if (! $this->checklists->hasTypeStarted($user, $typeCode)) {
            return 'Not started';
        }

        $percent = match ($typeCode) {
            'fap' => $this->dashboardStats->apprenticeshipPercent($user),
            'licensing' => $this->dashboardStats->licensingPercent($user),
            default => 0,
        };

        return $percent.'%';
    }

    /**
     * @return list<array{month: string, completed: int, started: int}>
     */
    private function monthlyActivity(User $user): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        return $months->map(function ($month) use ($user): array {
            $start = $month->copy();
            $end = $month->copy()->endOfMonth();

            $completed = TrainingProgress::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $started = TrainingProgress::query()
                ->where('user_id', $user->id)
                ->whereBetween('started_at', [$start, $end])
                ->count();

            return [
                'month' => $month->format('M'),
                'completed' => $completed,
                'started' => $started,
            ];
        })->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function learningPathsFor(User $user): Collection
    {
        return collect($this->paths->pathRowsFor($user))->map(fn (array $row): array => [
            'id' => $row['path']->id,
            'code' => $row['path']->code,
            'name' => $row['path']->name,
            'description' => $row['path']->description,
            'module_count' => $row['module_count'],
            'progress_percent' => $row['progress_percent'],
            'status' => $row['status'],
        ]);
    }

    /**
     * @return Collection<int, TrainingModule>
     */
    private function featuredCourses(): Collection
    {
        return TrainingModule::query()
            ->published()
            ->where('is_featured', true)
            ->with('category')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, UserTrainingBadge>
     */
    private function recentBadges(User $user): Collection
    {
        return UserTrainingBadge::query()
            ->with('badge')
            ->where('user_id', $user->id)
            ->latest('earned_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return list<array{label: string, route: string, code: string}>
     */
    private function checklistLinks(): array
    {
        return [
            ['label' => 'FAP Training Center', 'route' => 'apprenticeship.index', 'code' => 'fap'],
            ['label' => 'CFM Training Checklist', 'route' => 'cfm-training.index', 'code' => 'cfm-training'],
            ['label' => 'Licensing Tracker', 'route' => 'licensing.index', 'code' => 'licensing'],
        ];
    }
}
