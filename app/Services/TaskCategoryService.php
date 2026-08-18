<?php

namespace App\Services;

use App\Models\TaskCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskCategoryService
{
    /** @var Collection<int, TaskCategory>|null */
    private ?Collection $activeCategories = null;

    /**
     * @return Collection<int, TaskCategory>
     */
    public function activeCategories(): Collection
    {
        if ($this->activeCategories !== null) {
            return $this->activeCategories;
        }

        $this->activeCategories = TaskCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->activeCategories;
    }

    /**
     * @return list<string>
     */
    public function activeNames(): array
    {
        return $this->activeCategories()
            ->pluck('name')
            ->values()
            ->all();
    }

    public function findByName(?string $name): ?TaskCategory
    {
        if (! filled($name)) {
            return null;
        }

        return $this->activeCategories()->firstWhere('name', $name)
            ?? TaskCategory::query()->where('name', $name)->where('is_active', true)->first();
    }

    /**
     * @return array{url: string|null, label: string}|null
     */
    public function actionForName(?string $name): ?array
    {
        $category = $this->findByName($name);

        if (! $category) {
            return null;
        }

        $action = $category->actionLink();

        if (! filled($action['url'])) {
            return null;
        }

        return $action;
    }

    public function idForName(?string $name): ?int
    {
        if (! filled($name)) {
            return null;
        }

        return $this->findByName($name)?->id;
    }

    public function createCategory(string $name): TaskCategory
    {
        $name = trim($name);
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 1;

        while (TaskCategory::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $category = TaskCategory::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'sort_order' => (int) TaskCategory::query()->max('sort_order') + 10,
            'is_active' => true,
        ]);

        $this->activeCategories = null;

        return $category;
    }
}
