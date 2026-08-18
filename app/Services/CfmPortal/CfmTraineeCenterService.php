<?php

namespace App\Services\CfmPortal;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\Goal;
use App\Models\MentorAssignment;
use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\User;
use App\Models\UserTrainingCertification;
use App\Services\ChecklistService;
use App\Services\CfmTraineeChecklistService;
use App\Services\Goals\GoalCoachingService;
use App\Services\Training\TrainingAcademyDashboardService;
use Illuminate\Support\Collection;

class CfmTraineeCenterService
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly CfmTraineeChecklistService $mentoringChecklists,
        private readonly TrainingAcademyDashboardService $trainingAcademy,
        private readonly GoalCoachingService $goalCoaching,
    ) {}

    public function resolveTrainee(User $cfm, int $traineeId): ?User
    {
        return User::query()
            ->whereKey($traineeId)
            ->where('mentor_id', $cfm->id)
            ->whereKeyNot($cfm->id)
            ->with(['profile', 'rank', 'sponsor'])
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, string $section): ?array
    {
        $trainee = $this->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $trainee->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return match ($section) {
            'onboarding' => $this->onboardingCenter($cfm, $trainee),
            'fap' => $this->fapCenter($cfm, $trainee, $assignment),
            'licensing' => $this->licensingCenter($cfm, $trainee),
            'training' => $this->trainingCenter($trainee),
            'goals' => $this->goalsCenter($trainee),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function onboardingCenter(User $cfm, User $trainee): array
    {
        return $this->buildChecklistCenter($cfm, $trainee, [
            'key' => 'onboarding',
            'type_code' => 'onboarding',
            'group_label' => null,
            'title' => 'Onboarding Center',
            'description' => 'Registration, profile completion, compliance training, and initial mentor touchpoints.',
            'empty_title' => 'Onboarding not started',
            'empty_description' => 'This trainee has not started the onboarding checklist yet.',
            'member_profile_url' => route('team.member.profile', $trainee),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function licensingCenter(User $cfm, User $trainee): array
    {
        return $this->buildChecklistCenter($cfm, $trainee, [
            'key' => 'licensing',
            'type_code' => 'licensing',
            'group_label' => null,
            'title' => 'Licensing Tracker',
            'description' => 'Courses, exams, licensing requirements, study progress, and CFM verification.',
            'empty_title' => 'Licensing not started',
            'empty_description' => 'This trainee has not started the licensing checklist yet.',
            'member_profile_url' => route('team.member.profile', $trainee),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fapCenter(User $cfm, User $trainee, ?MentorAssignment $assignment): array
    {
        $fap = $this->buildChecklistCenter($cfm, $trainee, [
            'key' => 'fap',
            'type_code' => 'fap',
            'group_label' => 'Field Apprenticeship Program',
            'title' => 'FAP Management Center',
            'description' => 'Field apprenticeship milestones, mentor evaluations, and CFM mentoring responsibilities.',
            'empty_title' => 'FAP not started',
            'empty_description' => 'This trainee has not started the Field Apprenticeship Program checklist yet.',
            'member_profile_url' => route('team.member.profile', $trainee),
        ]);

        $mentoring = null;

        if ($assignment) {
            $payload = $this->mentoringChecklists->checklistForAssignment($assignment);

            $mentoring = [
                'assignment_id' => $assignment->id,
                'stats' => $payload['stats'],
                'phases' => $payload['phases']->map(function (array $phase) {
                    return [
                        'phase_number' => $phase['phase_number'],
                        'phase_title' => $phase['phase_title'],
                        'phase_target' => $phase['phase_target'],
                        'total' => $phase['total'],
                        'completed' => $phase['completed'],
                        'percent' => $phase['percent'],
                        'sections' => collect($phase['sections'])->map(function (array $section) {
                            return [
                                'title' => $section['title'],
                                'items' => collect($section['items'])->map(function (array $item) {
                                    return [
                                        'id' => $item['id'],
                                        'title' => $item['title'],
                                        'description' => $item['description'],
                                        'is_required' => $item['is_required'],
                                        'is_completed' => $item['is_completed'],
                                        'completed_at' => $item['completed_at']?->format('M j, Y'),
                                        'expected_due_date' => $item['expected_due_date']?->format('M j, Y'),
                                        'notes' => $item['notes'],
                                        'action_url' => $item['action_url'],
                                        'action_label' => $item['action_label'],
                                    ];
                                })->values()->all(),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
                'checklist_url' => route('cfm.portal.trainees.checklist', $assignment),
            ];
        }

        return array_merge($fap, [
            'mentoring' => $mentoring,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trainingCenter(User $trainee): array
    {
        $dashboard = $this->trainingAcademy->dashboardFor($trainee);

        $assignments = TrainingAssignment::query()
            ->where('user_id', $trainee->id)
            ->with(['module.category', 'assignedBy'])
            ->whereNotIn('status', ['cancelled'])
            ->latest('due_at')
            ->limit(12)
            ->get()
            ->map(fn (TrainingAssignment $assignment) => [
                'id' => $assignment->id,
                'title' => $assignment->module?->title ?? 'Training module',
                'category' => $assignment->module?->category?->name ?? 'General',
                'status' => $assignment->status,
                'due_at' => $assignment->due_at?->format('M j, Y') ?? '—',
                'is_overdue' => $assignment->due_at && $assignment->due_at->isPast() && $assignment->status !== 'completed',
                'assigned_by' => $assignment->assignedBy?->name ?? '—',
            ])
            ->values()
            ->all();

        $recentProgress = TrainingProgress::query()
            ->where('user_id', $trainee->id)
            ->with(['lesson.module'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (TrainingProgress $row) => [
                'lesson' => $row->lesson?->title ?? 'Lesson',
                'module' => $row->lesson?->module?->title ?? 'Module',
                'status' => $row->status,
                'updated_at' => $row->updated_at?->format('M j, Y') ?? '—',
            ])
            ->values()
            ->all();

        $certifications = UserTrainingCertification::query()
            ->where('user_id', $trainee->id)
            ->where('status', 'issued')
            ->with('certification')
            ->latest('issued_at')
            ->limit(6)
            ->get()
            ->map(fn ($cert) => [
                'name' => $cert->certification?->name ?? 'Certification',
                'issued_at' => $cert->issued_at?->format('M j, Y') ?? '—',
            ])
            ->values()
            ->all();

        $moduleProgress = TrainingModule::query()
            ->published()
            ->with('category')
            ->whereHas('lessons')
            ->withCount([
                'lessons',
                'lessons as completed_lessons_count' => function ($query) use ($trainee): void {
                    $query->whereHas('progress', function ($progressQuery) use ($trainee): void {
                        $progressQuery
                            ->where('user_id', $trainee->id)
                            ->where('status', 'completed');
                    });
                },
            ])
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(function (TrainingModule $module) {
                $total = (int) $module->lessons_count;
                $completed = (int) $module->completed_lessons_count;

                return [
                    'title' => $module->title,
                    'category' => $module->category?->name ?? 'General',
                    'progress' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                    'completed' => $completed,
                    'total' => $total,
                ];
            })
            ->values()
            ->all();

        return [
            'key' => 'training',
            'title' => 'Training Center',
            'description' => 'Assigned courses, quiz scores, certifications, and academy progress.',
            'stats' => [
                'lesson_completion_percent' => $dashboard['lesson_completion_percent'] ?? 0,
                'cards' => $dashboard['cards'] ?? [],
            ],
            'assignments' => $assignments,
            'recent_progress' => $recentProgress,
            'certifications' => $certifications,
            'modules' => $moduleProgress,
            'learning_paths' => $dashboard['learning_paths'] ?? collect(),
            'member_profile_url' => route('team.member.profile', $trainee),
            'training_url' => route('training.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function goalsCenter(User $trainee): array
    {
        $goals = Goal::query()
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['active', 'off_track', 'completed', 'draft'])
            ->with(['category', 'milestones'])
            ->latest()
            ->get();

        $active = $goals->whereIn('status', ['active', 'off_track']);

        $mapped = $goals->map(function (Goal $goal) {
            $progress = $goal->progressPercent();
            $pace = 'on_track';

            if ($goal->isOffTrack() || $goal->status === 'off_track') {
                $pace = 'off_track';
            } elseif ($goal->deadline_at && $progress >= 85) {
                $pace = 'ahead';
            } elseif ($goal->deadline_at && $progress < 50) {
                $pace = 'behind';
            }

            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'category' => $goal->category?->name ?? 'General',
                'category_slug' => $goal->category?->slug,
                'status' => $goal->status,
                'progress' => $progress,
                'pace' => $pace,
                'target' => $goal->formattedTarget(),
                'actual' => $goal->formattedActual(),
                'deadline' => $goal->deadline_at?->format('M j, Y') ?? '—',
                'milestones_total' => $goal->milestones->count(),
                'milestones_complete' => $goal->milestones->filter(fn ($milestone) => $milestone->isComplete())->count(),
            ];
        })->values()->all();

        $categoryBreakdown = collect($mapped)
            ->groupBy('category')
            ->map(fn (Collection $rows, string $category) => [
                'category' => $category,
                'count' => $rows->count(),
                'avg_progress' => (int) round($rows->avg('progress') ?: 0),
            ])
            ->values()
            ->all();

        return [
            'key' => 'goals',
            'title' => 'Goals & Performance',
            'description' => 'Income, production, recruiting, licensing, training, and rank goals with pace indicators.',
            'stats' => [
                'total' => $goals->count(),
                'active' => $active->count(),
                'off_track' => $goals->where('status', 'off_track')->count(),
                'completed' => $goals->where('status', 'completed')->count(),
                'avg_progress' => $active->isNotEmpty()
                    ? (int) round($active->avg(fn (Goal $goal) => $goal->progressPercent()))
                    : 0,
            ],
            'goals' => $mapped,
            'category_breakdown' => $categoryBreakdown,
            'suggestions' => $this->goalCoaching->suggestionsFor($trainee),
            'coaching_url' => route('goals.coaching'),
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function buildChecklistCenter(User $cfm, User $trainee, array $meta): array
    {
        $typeCode = $meta['type_code'];
        $groupLabel = $meta['group_label'] ?? null;
        $started = $this->checklists->hasTypeStarted($trainee, $typeCode);

        if (! $started) {
            return array_merge($meta, [
                'started' => false,
                'stats' => $this->emptyStats(),
                'items' => [],
                'pending_reviews' => [],
                'type_start_date' => null,
                'type_completion_due_date' => null,
                'type_max_complete_days' => $this->checklists->maxCompleteDaysForType($typeCode),
            ]);
        }

        $country = $typeCode === 'onboarding' ? $trainee->profile?->country : null;
        $steps = $this->checklists->activeSteps($typeCode, $country, $groupLabel);
        $typeStartDate = $this->checklists->typeStartDate($trainee, $typeCode);
        $typeCompletionDueDate = $typeStartDate
            ? $this->checklists->typeCompletionDueDate($typeStartDate, $typeCode)
            : null;

        $progress = $this->checklists->userProgressFor($trainee->id, $steps->pluck('id'));
        $completedByNames = User::query()
            ->whereIn('id', $progress->pluck('completed_by')->filter()->unique())
            ->pluck('name', 'id');

        $enrichedSteps = $this->checklists->enrichStepsWithSchedule(
            $steps->map(function (Checklist $step) use ($progress) {
                $step->progress = $progress->get($step->id);
                $step->item_status = $step->progress?->status ?? 'not_started';

                return $step;
            }),
            $typeStartDate,
        );

        $items = $enrichedSteps->map(function (Checklist $step) use ($completedByNames) {
            $stepProgress = $step->progress;
            $status = $step->item_status ?? 'not_started';

            return [
                'id' => $step->id,
                'progress_id' => $stepProgress?->id,
                'title' => $step->title,
                'description' => $step->description,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'is_completed' => $status === 'completed',
                'is_pending' => $status === 'pending_confirmation',
                'is_rejected' => $status === 'rejected',
                'is_required' => (bool) $step->is_required,
                'expected_due_date' => $step->expected_due_date?->format('M j, Y'),
                'completed_at' => $stepProgress?->completed_at?->format('M j, Y'),
                'completed_by' => $stepProgress?->completed_by
                    ? ($completedByNames[$stepProgress->completed_by] ?? '—')
                    : null,
                'submitted_at' => $stepProgress?->submitted_at?->format('M j, Y g:i A'),
                'review_comments' => $stepProgress?->review_comments,
                'notes' => $stepProgress?->notes,
            ];
        })->values()->all();

        $collection = collect($items);
        $total = $collection->count();
        $completed = $collection->where('is_completed', true)->count();
        $pending = $collection->where('is_pending', true)->count();
        $rejected = $collection->where('is_rejected', true)->count();
        $requiredTotal = $collection->where('is_required', true)->count();
        $requiredCompleted = $collection
            ->where('is_required', true)
            ->where('is_completed', true)
            ->count();

        $pendingReviews = $this->pendingReviewsFor($cfm, $trainee, $typeCode)
            ->map(fn (ChecklistProgress $row) => [
                'progress_id' => $row->id,
                'title' => $row->checklist?->title ?? 'Checklist item',
                'description' => $row->checklist?->description,
                'submitted_at' => $row->submitted_at?->format('M j, Y g:i A') ?? '—',
            ])
            ->values()
            ->all();

        return array_merge($meta, [
            'started' => true,
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'pending' => $pending,
                'rejected' => $rejected,
                'remaining' => max(0, $total - $completed),
                'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                'required_total' => $requiredTotal,
                'required_completed' => $requiredCompleted,
                'required_percent' => $requiredTotal > 0
                    ? (int) round(($requiredCompleted / $requiredTotal) * 100)
                    : 0,
            ],
            'items' => $items,
            'pending_reviews' => $pendingReviews,
            'type_start_date' => $typeStartDate?->format('M j, Y'),
            'type_completion_due_date' => $typeCompletionDueDate?->format('M j, Y'),
            'type_max_complete_days' => $this->checklists->maxCompleteDaysForType($typeCode),
        ]);
    }

    /**
     * @return Collection<int, ChecklistProgress>
     */
    private function pendingReviewsFor(User $cfm, User $trainee, string $typeCode): Collection
    {
        $typeId = $this->checklists->typeId($typeCode);

        if (! $typeId) {
            return collect();
        }

        return ChecklistProgress::query()
            ->with(['checklist', 'user'])
            ->where('user_id', $trainee->id)
            ->memberProgress()
            ->pendingConfirmation()
            ->whereHas('checklist', fn ($query) => $query
                ->where('checklist_type_id', $typeId)
                ->where('is_active', true))
            ->orderBy('submitted_at')
            ->get()
            ->filter(function (ChecklistProgress $progress) use ($cfm): bool {
                $record = (object) [
                    'sponsor_id' => $progress->user->sponsor_id,
                    'mentor_id' => $progress->user->mentor_id,
                    'notified_parties' => $progress->checklist->notified_parties,
                ];

                return $this->checklists->userCanConfirm($cfm, $record);
            })
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'rejected' => 0,
            'remaining' => 0,
            'percent' => 0,
            'required_total' => 0,
            'required_completed' => 0,
            'required_percent' => 0,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Complete',
            'pending_confirmation' => 'Pending review',
            'rejected' => 'Rejected',
            default => 'Not started',
        };
    }
}
