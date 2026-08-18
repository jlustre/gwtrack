<?php

namespace App\Services\Training;

use App\Models\TrainingAssignment;
use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingCoursePlayerService
{
    public function publishedModuleBySlug(string $slug): TrainingModule
    {
        return TrainingModule::query()
            ->published()
            ->where('slug', $slug)
            ->with(['category', 'instructor', 'lessons' => fn ($query) => $query->orderBy('sort_order')])
            ->firstOrFail();
    }

    public function ensureEnrollment(User $user, TrainingModule $module): TrainingAssignment
    {
        $assignment = TrainingAssignment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'training_module_id' => $module->id,
            ],
            [
                'status' => 'in_progress',
                'assigned_by' => null,
            ],
        );

        if ($assignment->status === 'assigned') {
            $assignment->update(['status' => 'in_progress']);
        }

        return $assignment->fresh();
    }

    public function courseStartDate(User $user, TrainingModule $module): CarbonInterface
    {
        $assignment = $this->ensureEnrollment($user, $module);

        return $assignment->created_at->copy()->startOfDay();
    }

    /**
     * @return Collection<int, TrainingProgress>
     */
    public function progressForModule(User $user, TrainingModule $module): Collection
    {
        return TrainingProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('training_lesson_id', $module->lessons->pluck('id'))
            ->get()
            ->keyBy('training_lesson_id');
    }

    /**
     * @return array{allowed: bool, reason: string|null, unlocks_at: CarbonInterface|null}
     */
    public function accessState(User $user, TrainingModule $module, TrainingLesson $lesson): array
    {
        abort_unless((int) $lesson->training_module_id === (int) $module->id, 404);

        $lessons = $module->lessons->values();
        $index = $lessons->search(fn (TrainingLesson $item) => (int) $item->id === (int) $lesson->id);

        if ($index === false) {
            return ['allowed' => false, 'reason' => 'unavailable', 'unlocks_at' => null];
        }

        $progress = $this->progressForModule($user, $module);
        $courseStart = $this->courseStartDate($user, $module);

        if ($module->drip_enabled && $index > 0) {
            $unlocksAt = $courseStart->copy()->addDays($index)->startOfDay();

            if (now()->startOfDay()->lt($unlocksAt)) {
                return [
                    'allowed' => false,
                    'reason' => 'drip',
                    'unlocks_at' => $unlocksAt,
                ];
            }
        }

        if ($module->sequential_required && $index > 0) {
            $previousIncomplete = $lessons
                ->slice(0, $index)
                ->first(fn (TrainingLesson $previous) => ! $progress->get($previous->id)?->isCompleted());

            if ($previousIncomplete) {
                return [
                    'allowed' => false,
                    'reason' => 'sequential',
                    'unlocks_at' => null,
                ];
            }
        }

        return ['allowed' => true, 'reason' => null, 'unlocks_at' => null];
    }

    /**
     * @return list<array{
     *     lesson: TrainingLesson,
     *     progress: TrainingProgress|null,
     *     status: string,
     *     locked: bool,
     *     lock_reason: string|null,
     *     unlocks_at: CarbonInterface|null
     * }>
     */
    public function lessonRows(User $user, TrainingModule $module): array
    {
        $progress = $this->progressForModule($user, $module);

        return $module->lessons->values()->map(function (TrainingLesson $lesson, int $index) use ($user, $module, $progress): array {
            $access = $this->accessState($user, $module, $lesson);
            $record = $progress->get($lesson->id);

            return [
                'lesson' => $lesson,
                'progress' => $record,
                'status' => $record?->status ?? 'not_started',
                'locked' => ! $access['allowed'],
                'lock_reason' => $access['reason'],
                'unlocks_at' => $access['unlocks_at'],
            ];
        })->all();
    }

    public function moduleProgressPercent(User $user, TrainingModule $module): int
    {
        $lessons = $module->lessons;
        $target = $lessons->where('is_required', true);

        if ($target->isEmpty()) {
            $target = $lessons;
        }

        if ($target->isEmpty()) {
            return 0;
        }

        $progress = $this->progressForModule($user, $module);
        $completed = $target->filter(
            fn (TrainingLesson $lesson) => $progress->get($lesson->id)?->isCompleted(),
        )->count();

        return (int) round(($completed / $target->count()) * 100);
    }

    public function startLesson(User $user, TrainingModule $module, TrainingLesson $lesson): TrainingProgress
    {
        $access = $this->accessState($user, $module, $lesson);

        if (! $access['allowed']) {
            throw ValidationException::withMessages([
                'lesson' => $access['reason'] === 'drip'
                    ? 'This lesson unlocks on '.$access['unlocks_at']?->format('M j, Y').'.'
                    : 'Complete the previous lessons before starting this one.',
            ]);
        }

        $this->ensureEnrollment($user, $module);

        $progress = TrainingProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'training_lesson_id' => $lesson->id,
        ]);

        if ($progress->isCompleted()) {
            return $progress;
        }

        $progress->status = 'in_progress';
        $progress->started_at ??= now();
        $progress->save();

        return $progress->fresh();
    }

    public function markLessonComplete(User $user, TrainingModule $module, TrainingLesson $lesson): TrainingProgress
    {
        $this->startLesson($user, $module, $lesson);

        $progress = TrainingProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'training_lesson_id' => $lesson->id,
            ],
            [
                'status' => 'in_progress',
                'started_at' => now(),
            ],
        );

        if ($progress->isCompleted()) {
            return $progress;
        }

        $progress->update([
            'status' => 'completed',
            'completed_at' => now(),
            'started_at' => $progress->started_at ?? now(),
        ]);

        $this->syncAssignmentCompletion($user, $module);

        app(TrainingGamificationService::class)->recordLessonCompleted($user);

        if ($this->moduleProgressPercent($user, $module) >= 100) {
            app(TrainingGamificationService::class)->recordCourseCompleted($user, $module);
        }

        app(TrainingRecommendationService::class)->syncForUser($user);

        return $progress->fresh();
    }

    public function reopenLesson(User $user, TrainingModule $module, TrainingLesson $lesson): TrainingProgress
    {
        abort_unless((int) $lesson->training_module_id === (int) $module->id, 404);

        $progress = TrainingProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'training_lesson_id' => $lesson->id,
            ],
            [
                'status' => 'not_started',
            ],
        );

        $progress->update([
            'status' => 'in_progress',
            'completed_at' => null,
            'started_at' => now(),
        ]);

        $this->syncAssignmentCompletion($user, $module);

        return $progress->fresh();
    }

    public function syncAssignmentCompletion(User $user, TrainingModule $module): void
    {
        $percent = $this->moduleProgressPercent($user, $module);

        TrainingAssignment::query()
            ->where('user_id', $user->id)
            ->where('training_module_id', $module->id)
            ->update([
                'status' => $percent >= 100 ? 'completed' : 'in_progress',
                'completed_at' => $percent >= 100 ? now() : null,
            ]);

        if ($percent >= 100) {
            app(TrainingCertificationService::class)->processCourseCompleted($user, $module);
        }

        app(TrainingPathService::class)->syncPathsForModule($user, $module);
    }

    /**
     * @return Collection<int, TrainingModule>
     */
    public function publishedCourses(): Collection
    {
        return TrainingModule::query()
            ->published()
            ->with(['category', 'lessons'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }
}
