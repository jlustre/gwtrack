<?php

namespace App\Services\Training;

use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingAssignmentService
{
    public function __construct(private readonly TrainingCoursePlayerService $courses) {}

    /**
     * @return list<array{
     *     assignment: TrainingAssignment,
     *     module: TrainingModule,
     *     progress_percent: int,
     *     is_overdue: bool
     * }>
     */
    public function rowsForUser(User $user): array
    {
        return TrainingAssignment::query()
            ->with(['module.category', 'assignedBy'])
            ->where('user_id', $user->id)
            ->whereNot('status', 'cancelled')
            ->latest('updated_at')
            ->get()
            ->map(function (TrainingAssignment $assignment) use ($user): array {
                $module = $assignment->module;
                $module?->loadMissing('lessons');

                return [
                    'assignment' => $assignment,
                    'module' => $module,
                    'progress_percent' => $module ? $this->courses->moduleProgressPercent($user, $module) : 0,
                    'is_overdue' => $this->isOverdue($assignment),
                ];
            })
            ->all();
    }

    public function assign(
        User $user,
        TrainingModule $module,
        User $assigner,
        ?\DateTimeInterface $dueAt = null,
        ?string $notes = null,
    ): TrainingAssignment {
        abort_unless($module->is_published && $module->status === 'published', 422);

        if ($dueAt === null) {
            $defaultDays = config('training-academy.assignments.default_due_days');
            $dueAt = $defaultDays ? now()->addDays((int) $defaultDays) : null;
        }

        $assignment = TrainingAssignment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'training_module_id' => $module->id,
            ],
            [
                'assigned_by' => $assigner->id,
                'status' => 'assigned',
                'due_at' => $dueAt,
                'notes' => $notes,
                'completed_at' => null,
            ],
        );

        app(TrainingRecommendationService::class)->syncForUser($user);

        return $assignment;
    }

    public function cancel(TrainingAssignment $assignment, User $actor): TrainingAssignment
    {
        abort_unless($actor->can('manage training'), 403);

        $assignment->update([
            'status' => 'cancelled',
        ]);

        return $assignment->fresh();
    }

    /**
     * @return Collection<int, TrainingModule>
     */
    public function assignableModules(): Collection
    {
        return TrainingModule::query()
            ->published()
            ->with('category')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function assignableUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);
    }

    public function isOverdue(TrainingAssignment $assignment): bool
    {
        if (in_array($assignment->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        return $assignment->due_at !== null && $assignment->due_at->isPast();
    }

    public function activeCountFor(User $user): int
    {
        return TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    public function overdueCountFor(User $user): int
    {
        return TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
    }
}
