<?php

namespace App\Services\Training;

use App\Models\AssessmentAttempt;
use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\TrainingSessionAttendance;
use App\Models\User;
use App\Models\UserTrainingCertification;
use App\Models\UserTrainingGamificationProfile;
use App\Models\UserTrainingPathEnrollment;
use App\Services\DownlineHierarchyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class TrainingReportService
{
    public function __construct(
        private readonly TrainingCoursePlayerService $courses,
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function canUseScope(User $viewer, string $scope): bool
    {
        return match ($scope) {
            'personal' => true,
            'directs' => $viewer->can('view own team'),
            'downline' => $viewer->can('view training summary'),
            'organization' => $viewer->can('manage training'),
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public function availableScopesFor(User $viewer): array
    {
        $scopes = ['personal'];

        if ($viewer->can('view own team')) {
            $scopes[] = 'directs';
        }

        if ($viewer->can('view training summary')) {
            $scopes[] = 'downline';
        }

        if ($viewer->can('manage training')) {
            $scopes[] = 'organization';
        }

        return $scopes;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(User $viewer, string $periodType, string $scope = 'personal'): array
    {
        abort_unless($this->canUseScope($viewer, $scope), 403);

        [$start, $end, $label] = $this->periodBounds($periodType);
        $userIds = $this->userIdsForScope($viewer, $scope);

        $summary = $this->summaryMetrics($userIds, $start, $end, $scope);
        $monthlyTrend = $this->monthlyTrend($userIds, 6);
        $topCourses = $this->topCoursesInPeriod($userIds, $start, $end);
        $memberRows = $this->memberRows($userIds, $start, $end, $scope);
        $courseRows = $scope === 'personal'
            ? $this->personalCourseRows($viewer)
            : [];

        return [
            'viewer' => $viewer,
            'scope' => $scope,
            'scope_label' => $this->scopeLabel($scope),
            'period_type' => $periodType,
            'period_label' => $label,
            'period_start' => $start,
            'period_end' => $end,
            'generated_at' => now(),
            'summary' => $summary,
            'monthly_trend' => $monthlyTrend,
            'top_courses' => $topCourses,
            'member_rows' => $memberRows,
            'course_rows' => $courseRows,
        ];
    }

    public function renderHtml(User $viewer, string $periodType, string $scope): string
    {
        return view('training.reports.pdf', $this->buildReportData($viewer, $periodType, $scope))->render();
    }

    public function downloadPdf(User $viewer, string $periodType, string $scope): Response
    {
        $pdf = Pdf::loadHTML($this->renderHtml($viewer, $periodType, $scope))
            ->setPaper('letter', 'landscape');

        $filename = sprintf(
            'training-report-%s-%s-%s.pdf',
            $scope,
            $periodType,
            now()->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    public function sendEmail(User $viewer, string $periodType, string $scope): void
    {
        $data = $this->buildReportData($viewer, $periodType, $scope);

        Mail::to($viewer->email)->send(new \App\Mail\TrainingReportMail($viewer, $data));
    }

    /**
     * @return list<int>
     */
    private function userIdsForScope(User $viewer, string $scope): array
    {
        return $this->scopedMembersQuery($viewer, $scope)->pluck('id')->all();
    }

    private function scopedMembersQuery(User $viewer, string $scope): Builder
    {
        return match ($scope) {
            'personal' => User::query()->whereKey($viewer->id),
            'directs' => $this->hierarchy->directRecruitsQuery($viewer)->select('users.*'),
            'organization' => User::query(),
            default => $this->hierarchy->dashboardMembersQuery($viewer)->select('users.*'),
        };
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, int|float>
     */
    private function summaryMetrics(array $userIds, Carbon $start, Carbon $end, string $scope): array
    {
        $lessonsCompleted = TrainingProgress::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $coursesCompleted = TrainingAssignment::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $assessmentsPassed = AssessmentAttempt::query()
            ->whereIn('user_id', $userIds)
            ->where('passed', true)
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $certificationsIssued = UserTrainingCertification::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$start, $end])
            ->count();

        $trainingSeconds = (int) TrainingProgress::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('updated_at', [$start, $end])
            ->sum('time_spent_seconds');

        $assignmentsOverdue = TrainingAssignment::query()
            ->whereIn('user_id', $userIds)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $pathCompletions = UserTrainingPathEnrollment::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $sessionAttendance = TrainingSessionAttendance::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'attended')
            ->whereBetween('checked_in_at', [$start, $end])
            ->count();

        $activeLearners = TrainingProgress::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('completed_at', [$start, $end])
            ->distinct('user_id')
            ->count('user_id');

        $avgCourseProgress = $scope === 'personal'
            ? $this->averageCourseProgressForUser($userIds[0] ?? 0)
            : $this->averageCourseProgressForUsers($userIds);

        return [
            'lessons_completed' => $lessonsCompleted,
            'courses_completed' => $coursesCompleted,
            'assessments_passed' => $assessmentsPassed,
            'certifications_issued' => $certificationsIssued,
            'training_hours' => round($trainingSeconds / 3600, 1),
            'assignments_overdue' => $assignmentsOverdue,
            'path_completions' => $pathCompletions,
            'session_attendance' => $sessionAttendance,
            'active_learners' => $activeLearners,
            'avg_course_progress' => $avgCourseProgress,
            'members_in_scope' => count($userIds),
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @return list<array{month: string, lessons_completed: int}>
     */
    private function monthlyTrend(array $userIds, int $months): array
    {
        return collect(range($months - 1, 0))->map(function (int $offset) use ($userIds): array {
            $month = now()->subMonths($offset)->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $count = TrainingProgress::query()
                ->whereIn('user_id', $userIds)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$month, $end])
                ->count();

            return [
                'month' => $month->format('M Y'),
                'lessons_completed' => $count,
            ];
        })->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return list<array{title: string, completions: int}>
     */
    private function topCoursesInPeriod(array $userIds, Carbon $start, Carbon $end): array
    {
        return TrainingAssignment::query()
            ->selectRaw('training_module_id, count(*) as completions')
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->groupBy('training_module_id')
            ->orderByDesc('completions')
            ->limit(5)
            ->get()
            ->map(function ($row): array {
                $module = TrainingModule::query()->find($row->training_module_id);

                return [
                    'title' => $module?->title ?? 'Unknown course',
                    'completions' => (int) $row->completions,
                ];
            })
            ->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return list<array<string, mixed>>
     */
    private function memberRows(array $userIds, Carbon $start, Carbon $end, string $scope): array
    {
        if ($scope === 'personal') {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function (User $member) use ($start, $end): array {
                $lessons = TrainingProgress::query()
                    ->where('user_id', $member->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$start, $end])
                    ->count();

                $courses = TrainingAssignment::query()
                    ->where('user_id', $member->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$start, $end])
                    ->count();

                $points = UserTrainingGamificationProfile::query()
                    ->where('user_id', $member->id)
                    ->value('total_points') ?? 0;

                return [
                    'name' => $member->name,
                    'lessons_completed' => $lessons,
                    'courses_completed' => $courses,
                    'avg_progress' => $this->averageCourseProgressForUser($member->id),
                    'points' => (int) $points,
                ];
            })
            ->sortByDesc('lessons_completed')
            ->values()
            ->all();
    }

    /**
     * @return list<array{title: string, progress_percent: int, status: string}>
     */
    private function personalCourseRows(User $user): array
    {
        return $this->courses->publishedCourses()->map(function (TrainingModule $module) use ($user): array {
            $percent = $this->courses->moduleProgressPercent($user, $module);

            return [
                'title' => $module->title,
                'progress_percent' => $percent,
                'status' => $percent >= 100 ? 'Completed' : ($percent > 0 ? 'In progress' : 'Not started'),
            ];
        })->all();
    }

    private function averageCourseProgressForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return 0;
        }

        $modules = $this->courses->publishedCourses();

        if ($modules->isEmpty()) {
            return 0;
        }

        $total = $modules->sum(fn (TrainingModule $module) => $this->courses->moduleProgressPercent($user, $module));

        return (int) round($total / $modules->count());
    }

    /**
     * @param  list<int>  $userIds
     */
    private function averageCourseProgressForUsers(array $userIds): int
    {
        if ($userIds === []) {
            return 0;
        }

        $total = collect($userIds)->avg(fn (int $userId) => $this->averageCourseProgressForUser($userId));

        return (int) round($total);
    }

    private function scopeLabel(string $scope): string
    {
        return match ($scope) {
            'personal' => 'Personal',
            'directs' => 'Direct Recruits',
            'downline' => 'Team Downline',
            'organization' => 'Organization',
            default => str($scope)->title(),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function periodBounds(string $periodType): array
    {
        return match ($periodType) {
            'weekly' => [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
                'Weekly',
            ],
            'quarterly' => [
                now()->subQuarter()->startOfQuarter(),
                now()->subQuarter()->endOfQuarter(),
                'Quarterly',
            ],
            'annual' => [
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
                'Annual',
            ],
            default => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Monthly',
            ],
        };
    }
}
