<?php

namespace App\Services\Training;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Checklist;
use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\TrainingPath;
use App\Models\TrainingProgress;
use App\Models\TrainingRecommendation;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\DashboardStatsService;
use Illuminate\Support\Collection;

class TrainingRecommendationService
{
    public function __construct(
        private readonly TrainingCoursePlayerService $courses,
        private readonly TrainingPathService $paths,
        private readonly ChecklistService $checklists,
        private readonly DashboardStatsService $dashboardStats,
    ) {}

    public function syncForUser(User $user): void
    {
        $candidates = $this->buildCandidates($user);

        $fingerprints = collect($candidates)->map(fn (array $candidate): string => $this->fingerprint($candidate))->all();

        TrainingRecommendation::query()
            ->where('user_id', $user->id)
            ->whereNull('dismissed_at')
            ->get()
            ->each(function (TrainingRecommendation $recommendation) use ($fingerprints): void {
                if (! in_array($this->fingerprintFromModel($recommendation), $fingerprints, true)) {
                    $recommendation->delete();
                }
            });

        foreach ($candidates as $candidate) {
            $this->upsertRecommendation($user, $candidate);
        }
    }

    /**
     * @return list<array{
     *     recommendation: TrainingRecommendation,
     *     label: string,
     *     action_url: string|null,
     *     action_label: string|null,
     *     priority: int
     * }>
     */
    public function recommendationRowsFor(User $user, int $limit = 5): array
    {
        $this->syncForUser($user);

        return TrainingRecommendation::query()
            ->with(['module', 'path'])
            ->where('user_id', $user->id)
            ->whereNull('dismissed_at')
            ->orderByDesc('priority')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (TrainingRecommendation $recommendation): array => $this->rowFor($recommendation))
            ->all();
    }

    /**
     * @return array{
     *     audience_path: TrainingPath|null,
     *     enrolled_paths: list<array<string, mixed>>,
     *     priority_rows: list<array<string, mixed>>,
     *     all_rows: list<array<string, mixed>>,
     *     stats: array<string, int|string|null>
     * }
     */
    public function learningPlanFor(User $user): array
    {
        $this->syncForUser($user);

        $allRows = $this->recommendationRowsFor($user, 25);
        $audiencePath = $this->suggestedPathFor($user);

        $enrolledPaths = collect($this->paths->pathRowsFor($user))
            ->filter(fn (array $row): bool => $row['enrollment'] !== null)
            ->map(function (array $row) use ($user): array {
                $detail = $this->paths->pathDetailFor($user, $row['path']);
                $nextModule = collect($detail['module_rows'])
                    ->first(fn (array $moduleRow): bool => $moduleRow['progress_percent'] < 100);

                return [
                    'path' => $row['path'],
                    'progress_percent' => $row['progress_percent'],
                    'status' => $row['status'],
                    'next_module' => $nextModule['module'] ?? null,
                    'next_module_progress' => $nextModule['progress_percent'] ?? 0,
                ];
            })
            ->values()
            ->all();

        $publishedCount = TrainingModule::query()->published()->count();
        $completedCount = TrainingModule::query()
            ->published()
            ->whereHas('lessons')
            ->get()
            ->filter(fn (TrainingModule $module): bool => $this->courses->moduleProgressPercent($user, $module) >= 100)
            ->count();

        return [
            'audience_path' => $audiencePath,
            'enrolled_paths' => $enrolledPaths,
            'priority_rows' => array_slice($allRows, 0, 8),
            'all_rows' => $allRows,
            'stats' => [
                'courses_completed' => $completedCount,
                'courses_available' => $publishedCount,
                'active_assignments' => TrainingAssignment::query()
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'suggested_path' => $audiencePath?->name,
            ],
        ];
    }

    public function dismiss(TrainingRecommendation $recommendation, User $user): void
    {
        abort_unless((int) $recommendation->user_id === (int) $user->id, 403);

        $recommendation->update(['dismissed_at' => now()]);
    }

    /**
     * @return list<array{
     *     reason_code: string,
     *     message: string,
     *     priority: int,
     *     training_module_id?: int|null,
     *     training_path_id?: int|null
     * }>
     */
    private function buildCandidates(User $user): array
    {
        $candidates = [];

        foreach ($this->overdueAssignmentCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->continueCourseCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->pathCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->assessmentCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->checklistCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->inactivityCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($this->featuredCandidates($user) as $candidate) {
            $candidates[] = $candidate;
        }

        if ($candidates === []) {
            $candidates[] = [
                'reason_code' => 'on_track',
                'message' => 'You are on pace. Explore featured courses to continue your professional development.',
                'priority' => $this->priorityFor('on_track'),
            ];
        }

        return collect($candidates)
            ->unique(fn (array $candidate): string => $this->fingerprint($candidate))
            ->sortByDesc('priority')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function overdueAssignmentCandidates(User $user): array
    {
        return TrainingAssignment::query()
            ->with('module')
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get()
            ->map(fn (TrainingAssignment $assignment): array => [
                'reason_code' => 'overdue_assignment',
                'message' => sprintf(
                    'Overdue: finish %s (due %s).',
                    $assignment->module?->title ?? 'assigned course',
                    $assignment->due_at?->format('M j, Y'),
                ),
                'priority' => $this->priorityFor('overdue_assignment'),
                'training_module_id' => $assignment->training_module_id,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function continueCourseCandidates(User $user): array
    {
        return TrainingAssignment::query()
            ->with('module')
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->get()
            ->map(function (TrainingAssignment $assignment) use ($user): ?array {
                $module = $assignment->module;

                if (! $module) {
                    return null;
                }

                $percent = $this->courses->moduleProgressPercent($user, $module);

                if ($percent <= 0 || $percent >= 100) {
                    return null;
                }

                return [
                    'reason_code' => 'continue_course',
                    'message' => sprintf('Continue %s — you are %d%% complete.', $module->title, $percent),
                    'priority' => $this->priorityFor('continue_course'),
                    'training_module_id' => $module->id,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pathCandidates(User $user): array
    {
        $candidates = [];
        $suggestedPath = $this->suggestedPathFor($user);

        if ($suggestedPath && ! $this->paths->enrollmentFor($user, $suggestedPath)) {
            $candidates[] = [
                'reason_code' => 'enroll_path',
                'message' => sprintf('Enroll in the %s learning path tailored to your role.', $suggestedPath->name),
                'priority' => $this->priorityFor('enroll_path'),
                'training_path_id' => $suggestedPath->id,
            ];
        }

        foreach ($this->paths->pathRowsFor($user) as $row) {
            if ($row['status'] === 'completed' || ! $row['enrollment']) {
                continue;
            }

            $detail = $this->paths->pathDetailFor($user, $row['path']);
            $next = collect($detail['module_rows'])->first(
                fn (array $moduleRow): bool => $moduleRow['progress_percent'] < 100
            );

            if (! $next) {
                continue;
            }

            $module = $next['module'];

            $candidates[] = [
                'reason_code' => 'path_next_course',
                'message' => sprintf(
                    'Next in %s: %s (%d%% complete).',
                    $row['path']->name,
                    $module->title,
                    $next['progress_percent'],
                ),
                'priority' => $this->priorityFor('path_next_course'),
                'training_module_id' => $module->id,
                'training_path_id' => $row['path']->id,
            ];
        }

        return $candidates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assessmentCandidates(User $user): array
    {
        return Assessment::query()
            ->published()
            ->with('module')
            ->whereNotNull('training_module_id')
            ->whereHas('questions')
            ->get()
            ->filter(function (Assessment $assessment) use ($user): bool {
                $module = $assessment->module;

                if (! $module || $this->courses->moduleProgressPercent($user, $module) < 100) {
                    return false;
                }

                return ! AssessmentAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('assessment_id', $assessment->id)
                    ->where('passed', true)
                    ->exists();
            })
            ->map(fn (Assessment $assessment): array => [
                'reason_code' => 'assessment_ready',
                'message' => sprintf('You finished %s. Take the assessment to earn certification credit.', $assessment->module?->title ?? 'the course'),
                'priority' => $this->priorityFor('assessment_ready'),
                'training_module_id' => $assessment->training_module_id,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function checklistCandidates(User $user): array
    {
        $candidates = [];

        if (! $this->checklists->hasTypeStarted($user, 'fap')) {
            $candidates[] = [
                'reason_code' => 'fap_not_started',
                'message' => 'Start your Field Apprenticeship Program to unlock field activity milestones.',
                'priority' => $this->priorityFor('fap_not_started'),
            ];
        } elseif ($this->dashboardStats->apprenticeshipPercent($user) < 100) {
            $candidates[] = [
                'reason_code' => 'fap_in_progress',
                'message' => sprintf(
                    'Your FAP checklist is %d%% complete. Keep momentum with field activities and coaching.',
                    $this->dashboardStats->apprenticeshipPercent($user),
                ),
                'priority' => $this->priorityFor('fap_in_progress'),
            ];
        }

        if ($this->checklists->hasTypeStarted($user, 'licensing')
            && $this->dashboardStats->licensingPercent($user) < config('training-academy.recommendations.licensing_behind_percent', 50)) {
            $candidates[] = [
                'reason_code' => 'licensing_behind',
                'message' => 'You are behind on licensing milestones. Complete licensing courses in your learning path.',
                'priority' => $this->priorityFor('licensing_behind'),
            ];
        }

        if ($user->hasAnyRole(['certified-field-mentor', 'trainer'])
            && $this->checklists->hasTypeStarted($user, 'cfm-training')) {
            $cfmPercent = $this->checklistPercentForType($user, 'cfm-training');

            if ($cfmPercent < 100) {
                $candidates[] = [
                    'reason_code' => 'cfm_training',
                    'message' => sprintf(
                        'Continue CFM Training — %d%% complete toward certification readiness.',
                        $cfmPercent,
                    ),
                    'priority' => $this->priorityFor('cfm_training'),
                ];
            }
        }

        return $candidates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inactivityCandidates(User $user): array
    {
        $lastActivity = TrainingProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->max('completed_at');

        if (! $lastActivity) {
            return [];
        }

        $inactiveDays = (int) config('training-academy.recommendations.inactive_days', 14);

        if (now()->diffInDays($lastActivity) < $inactiveDays) {
            return [];
        }

        $module = TrainingModule::query()
            ->published()
            ->get()
            ->first(fn (TrainingModule $published): bool => $this->courses->moduleProgressPercent($user, $published) > 0
                && $this->courses->moduleProgressPercent($user, $published) < 100);

        if (! $module) {
            return [[
                'reason_code' => 'inactive_learning',
                'message' => 'It has been a while since your last lesson. Pick a featured course to restart your learning rhythm.',
                'priority' => $this->priorityFor('inactive_learning'),
            ]];
        }

        return [[
            'reason_code' => 'inactive_learning',
            'message' => sprintf(
                'Welcome back. Continue %s where you left off.',
                $module->title,
            ),
            'priority' => $this->priorityFor('inactive_learning'),
            'training_module_id' => $module->id,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function featuredCandidates(User $user): array
    {
        return TrainingModule::query()
            ->published()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit(2)
            ->get()
            ->filter(fn (TrainingModule $module): bool => $this->courses->moduleProgressPercent($user, $module) === 0)
            ->map(fn (TrainingModule $module): array => [
                'reason_code' => 'featured_course',
                'message' => sprintf('Explore featured course: %s.', $module->title),
                'priority' => $this->priorityFor('featured_course'),
                'training_module_id' => $module->id,
            ])
            ->values()
            ->all();
    }

    private function suggestedPathFor(User $user): ?TrainingPath
    {
        $rolePaths = config('training-academy.recommendations.role_paths', []);

        foreach ($user->getRoleNames() as $role) {
            if (! empty($rolePaths[$role])) {
                return TrainingPath::query()
                    ->where('code', $rolePaths[$role])
                    ->where('is_active', true)
                    ->first();
            }
        }

        return TrainingPath::query()
            ->where('code', 'new-associate')
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function upsertRecommendation(User $user, array $candidate): void
    {
        $query = TrainingRecommendation::query()
            ->where('user_id', $user->id)
            ->where('reason_code', $candidate['reason_code']);

        if (! empty($candidate['training_module_id'])) {
            $query->where('training_module_id', $candidate['training_module_id']);
        } else {
            $query->whereNull('training_module_id');
        }

        if (! empty($candidate['training_path_id'])) {
            $query->where('training_path_id', $candidate['training_path_id']);
        } else {
            $query->whereNull('training_path_id');
        }

        if ($query->whereNotNull('dismissed_at')->exists()) {
            return;
        }

        TrainingRecommendation::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'reason_code' => $candidate['reason_code'],
                'training_module_id' => $candidate['training_module_id'] ?? null,
                'training_path_id' => $candidate['training_path_id'] ?? null,
            ],
            [
                'message' => $candidate['message'],
                'priority' => $candidate['priority'],
                'dismissed_at' => null,
            ],
        );
    }

    /**
     * @return array{
     *     recommendation: TrainingRecommendation,
     *     label: string,
     *     action_url: string|null,
     *     action_label: string|null,
     *     priority: int
     * }
     */
    private function rowFor(TrainingRecommendation $recommendation): array
    {
        $reason = $recommendation->reason_code;

        return [
            'recommendation' => $recommendation,
            'label' => config('training-academy.recommendations.reasons.'.$reason.'.label', str($reason)->replace('_', ' ')->title()),
            'action_url' => $this->actionUrlFor($recommendation),
            'action_label' => config('training-academy.recommendations.reasons.'.$reason.'.action', 'Open'),
            'priority' => $recommendation->priority,
        ];
    }

    private function actionUrlFor(TrainingRecommendation $recommendation): ?string
    {
        if ($recommendation->module) {
            if ($recommendation->reason_code === 'assessment_ready') {
                $assessment = Assessment::query()
                    ->published()
                    ->where('training_module_id', $recommendation->module->id)
                    ->whereHas('questions')
                    ->first();

                return $assessment ? route('assessments.take', $assessment) : route('training.courses.show', $recommendation->module);
            }

            return route('training.courses.show', $recommendation->module);
        }

        if ($recommendation->path) {
            return route('training.paths.show', $recommendation->path);
        }

        return match ($recommendation->reason_code) {
            'fap_not_started', 'fap_in_progress' => route('apprenticeship.index'),
            'licensing_behind' => route('licensing.index'),
            'cfm_training' => route('cfm-training.index'),
            default => route('training.plan.index'),
        };
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function fingerprint(array $candidate): string
    {
        return implode(':', [
            $candidate['reason_code'],
            $candidate['training_module_id'] ?? '0',
            $candidate['training_path_id'] ?? '0',
        ]);
    }

    private function fingerprintFromModel(TrainingRecommendation $recommendation): string
    {
        return implode(':', [
            $recommendation->reason_code,
            $recommendation->training_module_id ?? '0',
            $recommendation->training_path_id ?? '0',
        ]);
    }

    private function priorityFor(string $reasonCode): int
    {
        return (int) config('training-academy.recommendations.reasons.'.$reasonCode.'.priority', 40);
    }

    private function checklistPercentForType(User $user, string $typeCode): int
    {
        if (! $this->checklists->hasTypeStarted($user, $typeCode)) {
            return 0;
        }

        $stepIds = Checklist::query()
            ->forTypeCode($typeCode)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        return $this->checklists->checklistPercent($stepIds, $user->id);
    }
}
