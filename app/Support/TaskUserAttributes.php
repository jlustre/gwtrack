<?php

namespace App\Support;

class TaskUserAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function withCategory(string $categoryName, array $attributes): array
    {
        return self::forCategoryTask($categoryName, $attributes);
    }

    /**
     * Assign using the default library task for a category.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forCategoryTask(string $categoryName, array $attributes): array
    {
        $task = TaskLibrary::forCategory($categoryName);

        return array_merge($attributes, [
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
        ]);
    }

    /**
     * Assign using a specific library task title (created if missing).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forTask(
        string $categoryName,
        string $taskTitle,
        array $attributes,
        ?string $taskDescription = null,
        ?string $defaultPriority = null,
    ): array {
        $task = TaskLibrary::findOrCreate($taskTitle, $categoryName, $taskDescription, $defaultPriority);

        return array_merge($attributes, [
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
        ]);
    }

    /**
     * System-triggered assignment using assignor_id 999.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forSystemCategoryTask(string $categoryName, array $attributes): array
    {
        return self::forCategoryTask($categoryName, self::withSystemAssignor($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forSystemTask(
        string $categoryName,
        string $taskTitle,
        array $attributes,
        ?string $taskDescription = null,
        ?string $defaultPriority = null,
    ): array {
        return self::forTask(
            $categoryName,
            $taskTitle,
            self::withSystemAssignor($attributes),
            $taskDescription,
            $defaultPriority,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function withSystemAssignor(array $attributes): array
    {
        return array_merge($attributes, [
            'assignor_id' => SystemTaskAssignor::id(),
        ]);
    }
}
