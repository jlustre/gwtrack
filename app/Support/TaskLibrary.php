<?php

namespace App\Support;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Services\TaskCategoryService;
use Illuminate\Support\Str;

class TaskLibrary
{
    public static function findOrCreate(
        string $title,
        string $categoryName,
        ?string $description = null,
        ?string $defaultPriority = null,
    ): Task {
        $categoryId = app(TaskCategoryService::class)->idForName($categoryName)
            ?? self::fallbackCategoryId($categoryName);

        $slug = Str::slug($title);

        return Task::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'task_category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'default_priority' => $defaultPriority ?? 'medium',
                'is_active' => true,
            ],
        );
    }

    public static function forCategory(string $categoryName): Task
    {
        $categoryId = app(TaskCategoryService::class)->idForName($categoryName)
            ?? self::fallbackCategoryId($categoryName);

        $task = Task::query()
            ->where('task_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->first();

        if ($task) {
            return $task;
        }

        return self::findOrCreate(
            self::defaultTitleForCategory($categoryName),
            $categoryName,
            self::defaultDescriptionForCategory($categoryName),
        );
    }

    private static function defaultTitleForCategory(string $categoryName): string
    {
        return match ($categoryName) {
            'Prospect Follow-Up' => 'Follow up with a prospect',
            'FNA' => 'Complete FNA follow-up',
            'Licensing' => 'Complete licensing milestone',
            'CFM Mentorship' => 'Schedule mentor review session',
            default => $categoryName.' task',
        };
    }

    private static function fallbackCategoryId(string $categoryName): int
    {
        $category = TaskCategory::query()->where('slug', Str::slug($categoryName))->first()
            ?? TaskCategory::query()->where('slug', 'admin')->first();

        if ($category) {
            return (int) $category->id;
        }

        return (int) TaskCategory::query()->create([
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'description' => self::defaultDescriptionForCategory($categoryName),
            'action_route' => 'tasks.index',
            'action_label' => 'Open Tasks',
            'icon' => 'check-circle',
            'accent_class' => 'border-sky-200 bg-sky-50 text-sky-800',
            'sort_order' => 900,
            'is_active' => true,
        ])->id;
    }

    private static function defaultDescriptionForCategory(string $categoryName): ?string
    {
        return match ($categoryName) {
            'Prospect Follow-Up' => 'Reach out to an active prospect and log the activity in CRM.',
            'FNA' => 'Complete the next financial needs analysis workflow step.',
            'Licensing' => 'Finish the next licensing checklist item or exam prep step.',
            'CFM Mentorship' => 'Book a mentorship check-in with your trainee or CFM.',
            default => null,
        };
    }
}
