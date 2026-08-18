<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Http\Controllers\TaskController;
use App\Models\User;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

class TasksDueTodaySectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    public function __construct(
        private readonly TaskController $tasks,
    ) {}

    public function key(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Tasks';
    }

    public function icon(): string
    {
        return 'clipboard-check';
    }

    public function sort(): int
    {
        return 10;
    }

    public function isEnabledFor(User $user): bool
    {
        return Route::has('tasks.index');
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $preview = $this->tasks->openTasksByPriorityFor($user);

        $items = collect($preview['items'])
            ->values()
            ->map(function (array $item, int $index): array {
                $id = isset($item['id']) ? 'task-'.$item['id'] : 'task-'.$index;

                return [
                    'id' => $id,
                    'title' => $item['title'],
                    'subtitle' => $item['subtitle'] ?? $item['category'] ?? null,
                    'meta' => $item['meta'] ?? null,
                    'url' => $item['url'] ?? route('tasks.index'),
                    'badge' => $item['badge'] ?? null,
                    'highlight' => (bool) ($item['highlight'] ?? false),
                    'sort_at' => now()->toIso8601String(),
                ];
            })
            ->all();

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No open tasks assigned to you.',
            Route::has('tasks.index') ? ['label' => 'Open My Tasks', 'url' => route('tasks.index'), 'event' => 'open-my-tasks-modal'] : null,
        );
    }
}
