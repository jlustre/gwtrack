<?php

namespace App\Services\Training;

use App\Models\TrainingCategory;
use App\Models\TrainingCertification;
use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\TrainingPath;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrainingAdminService
{
    /**
     * @return array<string, int>
     */
    public function hubStats(): array
    {
        return [
            'courses_published' => TrainingModule::query()->published()->count(),
            'courses_draft' => TrainingModule::query()->where('status', 'draft')->count(),
            'lessons' => TrainingLesson::query()->count(),
            'paths' => TrainingPath::query()->where('is_active', true)->count(),
            'certifications' => TrainingCertification::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * @return Collection<int, TrainingModule>
     */
    public function coursesForAdmin(): Collection
    {
        return TrainingModule::query()
            ->with(['category', 'lessons'])
            ->withCount('lessons')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCourse(array $data): TrainingModule
    {
        $slug = $this->uniqueModuleSlug($data['slug'] ?? Str::slug($data['title']));

        return TrainingModule::query()->create([
            'training_category_id' => $data['training_category_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_published' => (bool) ($data['is_published'] ?? false),
            'status' => ($data['is_published'] ?? false) ? 'published' : 'draft',
            'course_type' => $data['course_type'] ?? 'video',
            'difficulty' => $data['difficulty'] ?? 'beginner',
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'instructor_id' => $data['instructor_id'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'sequential_required' => (bool) ($data['sequential_required'] ?? true),
            'drip_enabled' => (bool) ($data['drip_enabled'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCourse(TrainingModule $module, array $data): TrainingModule
    {
        $slug = $data['slug'] ?? $module->slug;

        if ($slug !== $module->slug) {
            $slug = $this->uniqueModuleSlug($slug, $module->id);
        }

        $isPublished = (bool) ($data['is_published'] ?? $module->is_published);

        $module->update([
            'training_category_id' => $data['training_category_id'] ?? $module->training_category_id,
            'title' => $data['title'] ?? $module->title,
            'slug' => $slug,
            'description' => $data['description'] ?? $module->description,
            'sort_order' => $data['sort_order'] ?? $module->sort_order,
            'is_published' => $isPublished,
            'status' => $isPublished ? 'published' : ($data['status'] ?? 'draft'),
            'course_type' => $data['course_type'] ?? $module->course_type,
            'difficulty' => $data['difficulty'] ?? $module->difficulty,
            'duration_minutes' => $data['duration_minutes'] ?? $module->duration_minutes,
            'instructor_id' => $data['instructor_id'] ?? $module->instructor_id,
            'is_featured' => (bool) ($data['is_featured'] ?? $module->is_featured),
            'sequential_required' => (bool) ($data['sequential_required'] ?? $module->sequential_required),
            'drip_enabled' => (bool) ($data['drip_enabled'] ?? $module->drip_enabled),
        ]);

        return $module->fresh(['category', 'lessons', 'instructor']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLesson(TrainingModule $module, array $data): TrainingLesson
    {
        $sortOrder = $data['sort_order'] ?? (($module->lessons()->max('sort_order') ?? 0) + 10);

        return TrainingLesson::query()->create([
            'training_module_id' => $module->id,
            'title' => $data['title'],
            'lesson_type' => $data['lesson_type'] ?? 'video',
            'content' => $data['content'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'resource_path' => $data['resource_path'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? true),
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLesson(TrainingLesson $lesson, array $data): TrainingLesson
    {
        abort_unless((int) $lesson->training_module_id === (int) ($data['training_module_id'] ?? $lesson->training_module_id), 422);

        $lesson->update([
            'title' => $data['title'] ?? $lesson->title,
            'lesson_type' => $data['lesson_type'] ?? $lesson->lesson_type,
            'content' => $data['content'] ?? $lesson->content,
            'video_url' => $data['video_url'] ?? $lesson->video_url,
            'resource_path' => $data['resource_path'] ?? $lesson->resource_path,
            'external_url' => $data['external_url'] ?? $lesson->external_url,
            'duration_minutes' => $data['duration_minutes'] ?? $lesson->duration_minutes,
            'is_required' => (bool) ($data['is_required'] ?? $lesson->is_required),
            'sort_order' => $data['sort_order'] ?? $lesson->sort_order,
        ]);

        return $lesson->fresh();
    }

    public function deleteLesson(TrainingLesson $lesson): void
    {
        $lesson->delete();
    }

    /**
     * @return Collection<int, TrainingPath>
     */
    public function pathsForAdmin(): Collection
    {
        return TrainingPath::query()
            ->withCount('modules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPath(array $data): TrainingPath
    {
        $code = $this->uniquePathCode($data['code'] ?? Str::slug($data['name']));

        return TrainingPath::query()->create([
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'audience' => $data['audience'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePath(TrainingPath $path, array $data): TrainingPath
    {
        $code = $data['code'] ?? $path->code;

        if ($code !== $path->code) {
            $code = $this->uniquePathCode($code, $path->id);
        }

        $path->update([
            'code' => $code,
            'name' => $data['name'] ?? $path->name,
            'description' => $data['description'] ?? $path->description,
            'audience' => $data['audience'] ?? $path->audience,
            'sort_order' => $data['sort_order'] ?? $path->sort_order,
            'is_active' => (bool) ($data['is_active'] ?? $path->is_active),
        ]);

        return $path->fresh();
    }

    /**
     * @param  list<array{module_id: int, sort_order?: int, is_required?: bool}>  $rows
     */
    public function syncPathModules(TrainingPath $path, array $rows): void
    {
        $sync = [];

        foreach ($rows as $index => $row) {
            if (empty($row['module_id'])) {
                continue;
            }

            $sync[(int) $row['module_id']] = [
                'sort_order' => (int) ($row['sort_order'] ?? (($index + 1) * 10)),
                'is_required' => (bool) ($row['is_required'] ?? true),
            ];
        }

        $path->modules()->sync($sync);
    }

    /**
     * @return Collection<int, TrainingCategory>
     */
    public function categories(): Collection
    {
        return TrainingCategory::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function instructors(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['trainer', 'certified-field-mentor', 'team-leader', 'agency-owner', 'super-admin', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * @return Collection<int, TrainingModule>
     */
    public function publishedModulesForPathBuilder(): Collection
    {
        return TrainingModule::query()
            ->published()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'difficulty']);
    }

    private function uniqueModuleSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'course';
        $candidate = $base;
        $counter = 1;

        while (
            TrainingModule::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniquePathCode(string $code, ?int $ignoreId = null): string
    {
        $base = Str::slug($code) ?: 'path';
        $candidate = $base;
        $counter = 1;

        while (
            TrainingPath::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
