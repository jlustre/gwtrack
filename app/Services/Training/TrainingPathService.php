<?php

namespace App\Services\Training;

use App\Models\TrainingModule;
use App\Models\TrainingPath;
use App\Models\User;
use App\Models\UserTrainingPathEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TrainingPathService
{
    public function __construct(private readonly TrainingCoursePlayerService $courses) {}

    /**
     * @return Collection<int, TrainingPath>
     */
    public function activePaths(): Collection
    {
        return TrainingPath::query()
            ->where('is_active', true)
            ->withCount('modules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{
     *     path: TrainingPath,
     *     enrollment: UserTrainingPathEnrollment|null,
     *     progress_percent: int,
     *     status: string,
     *     module_count: int
     * }>
     */
    public function pathRowsFor(User $user): array
    {
        return $this->activePaths()->map(function (TrainingPath $path) use ($user): array {
            $enrollment = $this->enrollmentFor($user, $path);

            if ($enrollment) {
                $this->syncEnrollmentProgress($user, $path);
                $enrollment = $enrollment->fresh();
            }

            return [
                'path' => $path,
                'enrollment' => $enrollment,
                'progress_percent' => $enrollment?->progress_percent ?? 0,
                'status' => $enrollment?->status ?? 'not_enrolled',
                'module_count' => $path->modules_count,
            ];
        })->all();
    }

    /**
     * @return array{
     *     path: TrainingPath,
     *     enrollment: UserTrainingPathEnrollment|null,
     *     progress_percent: int,
     *     status: string,
     *     module_rows: list<array{
     *         module: TrainingModule,
     *         is_required: bool,
     *         progress_percent: int,
     *         status: string
     *     }>
     * }
     */
    public function pathDetailFor(User $user, TrainingPath $path): array
    {
        abort_unless($path->is_active, 404);

        $path->load(['modules' => fn ($query) => $query->published()->with('category')]);

        $enrollment = $this->enrollmentFor($user, $path);

        if ($enrollment) {
            $this->syncEnrollmentProgress($user, $path);
            $enrollment = $enrollment->fresh();
        }

        $moduleRows = $path->modules->map(function (TrainingModule $module) use ($user): array {
            $progressPercent = $this->courses->moduleProgressPercent($user, $module);

            return [
                'module' => $module,
                'is_required' => (bool) $module->pivot->is_required,
                'progress_percent' => $progressPercent,
                'status' => $progressPercent >= 100 ? 'completed' : ($progressPercent > 0 ? 'in_progress' : 'not_started'),
            ];
        })->values()->all();

        return [
            'path' => $path,
            'enrollment' => $enrollment,
            'progress_percent' => $enrollment?->progress_percent ?? $this->pathProgressPercent($user, $path),
            'status' => $enrollment?->status ?? 'not_enrolled',
            'module_rows' => $moduleRows,
        ];
    }

    public function enroll(User $user, TrainingPath $path, ?User $assigner = null): UserTrainingPathEnrollment
    {
        abort_unless($path->is_active, 404);

        if ($path->modules()->published()->count() === 0) {
            throw ValidationException::withMessages([
                'path' => 'This learning path has no published courses yet.',
            ]);
        }

        $existing = $this->enrollmentFor($user, $path);

        if ($existing) {
            return $existing;
        }

        $enrollment = UserTrainingPathEnrollment::query()->create([
            'user_id' => $user->id,
            'training_path_id' => $path->id,
            'assigned_by' => $assigner?->id,
            'status' => 'in_progress',
            'progress_percent' => 0,
            'started_at' => now(),
        ]);

        $this->syncEnrollmentProgress($user, $path);

        app(TrainingRecommendationService::class)->syncForUser($user);

        return $enrollment->fresh();
    }

    public function syncPathsForModule(User $user, TrainingModule $module): void
    {
        $pathIds = $module->paths()->pluck('training_paths.id');

        UserTrainingPathEnrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('training_path_id', $pathIds)
            ->with('path')
            ->get()
            ->each(fn (UserTrainingPathEnrollment $enrollment) => $this->syncEnrollmentProgress($user, $enrollment->path));
    }

    public function syncEnrollmentProgress(User $user, TrainingPath $path): void
    {
        $enrollment = $this->enrollmentFor($user, $path);

        if (! $enrollment) {
            return;
        }

        $wasComplete = $enrollment->status === 'completed';

        $percent = $this->pathProgressPercent($user, $path);

        $enrollment->update([
            'progress_percent' => $percent,
            'status' => $percent >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $percent >= 100 ? ($enrollment->completed_at ?? now()) : null,
        ]);

        if ($percent >= 100 && ! $wasComplete) {
            app(TrainingGamificationService::class)->recordPathCompleted($user);
        }
    }

    public function pathProgressPercent(User $user, TrainingPath $path): int
    {
        $modules = $path->modules()->published()->get();
        $target = $modules->filter(fn (TrainingModule $module) => (bool) $module->pivot->is_required);

        if ($target->isEmpty()) {
            $target = $modules;
        }

        if ($target->isEmpty()) {
            return 0;
        }

        $total = $target->sum(fn (TrainingModule $module) => $this->courses->moduleProgressPercent($user, $module));

        return (int) round($total / $target->count());
    }

    public function enrollmentFor(User $user, TrainingPath $path): ?UserTrainingPathEnrollment
    {
        return UserTrainingPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('training_path_id', $path->id)
            ->first();
    }
}
