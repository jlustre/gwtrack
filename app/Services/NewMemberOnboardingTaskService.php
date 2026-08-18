<?php

namespace App\Services;

use App\Models\TaskUser;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Services\Notifications\NotificationOrchestrator;
use App\Support\SystemTaskAssignor;
use App\Support\TaskUserAttributes;
use Illuminate\Support\Collection;

class NewMemberOnboardingTaskService
{
    public const CATEGORY = 'Onboarding';

    public const TASK_TITLE = 'Schedule onboarding start date';

    public const TASK_SLUG = 'schedule-onboarding-start-date';

    public const TASK_DESCRIPTION = 'Open My Onboarding tracker and choose your Day 1 start date to begin your checklist.';

    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function assignIfNeeded(User $member): ?TaskUser
    {
        $member->loadMissing('profile');

        if ($this->hasOnboardingStarted($member)) {
            return null;
        }

        if ($this->openTaskFor($member) !== null) {
            return null;
        }

        $dueDate = now()->addDays(2)->toDateString();

        $taskUser = TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            self::CATEGORY,
            self::TASK_TITLE,
            [
                'assignee_id' => $member->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => 'Onboarding',
                'related_person' => $member->name,
                'due_date' => $dueDate,
            ],
            self::TASK_DESCRIPTION,
            'high',
        ));

        $this->notifications->dispatch('task_assigned', [
            'queue' => true,
            'recipients' => [$member->id],
            'module' => 'task',
            'priority' => 'high',
            'related' => ['type' => TaskUser::class, 'id' => $taskUser->id],
            'related_user_id' => $member->id,
            'template_data' => [
                'task_name' => self::TASK_TITLE,
                'cfm_name' => SystemTaskAssignor::NAME,
                'deadline' => $taskUser->due_date?->format('M j, Y') ?? 'Soon',
            ],
            'payload' => [
                'category' => self::CATEGORY,
                'task_id' => $taskUser->id,
            ],
            'action_link' => [
                'route' => 'onboarding.index',
                'label' => 'Open My Onboarding',
            ],
        ]);

        return $taskUser;
    }

    public function completeIfOpen(User $member, ?int $taskUserId = null): void
    {
        if ($taskUserId !== null) {
            $linkedTask = TaskUser::query()
                ->with(['task', 'taskCategory'])
                ->whereKey($taskUserId)
                ->where('assignee_id', $member->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->first();

            if ($linkedTask && $this->linksToOnboarding($linkedTask)) {
                $this->markCompleted($linkedTask);
            }
        }

        $this->openOnboardingStartTasksFor($member)
            ->each(fn (TaskUser $task): bool => $this->markCompleted($task));
    }

    public function openTaskFor(User $member): ?TaskUser
    {
        return $this->openOnboardingStartTasksFor($member)->first();
    }

    public static function taskSlug(): string
    {
        return self::TASK_SLUG;
    }

    public static function taskTitle(): string
    {
        return self::TASK_TITLE;
    }

    private function hasOnboardingStarted(User $member): bool
    {
        return UserChecklistTypeStart::query()
            ->where('user_id', $member->id)
            ->whereHas('checklistType', fn ($query) => $query->where('code', 'onboarding'))
            ->exists();
    }

    /**
     * @return Collection<int, TaskUser>
     */
    private function openOnboardingStartTasksFor(User $member): Collection
    {
        return TaskUser::query()
            ->with(['task', 'taskCategory'])
            ->where('assignee_id', $member->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('id')
            ->get()
            ->filter(fn (TaskUser $task): bool => $this->isOnboardingStartTask($task))
            ->values();
    }

    private function isOnboardingStartTask(TaskUser $task): bool
    {
        if ($task->task?->slug === self::TASK_SLUG) {
            return true;
        }

        if ($task->displayTitle() === self::TASK_TITLE) {
            return true;
        }

        $title = strtolower($task->displayTitle());

        return ($task->related_module === 'Onboarding' || $task->categoryName() === self::CATEGORY)
            && (str_contains($title, 'start date') || str_contains($title, 'day 1'));
    }

    private function linksToOnboarding(TaskUser $task): bool
    {
        return $this->isOnboardingStartTask($task)
            || $task->related_module === 'Onboarding'
            || $task->categoryName() === self::CATEGORY;
    }

    private function markCompleted(TaskUser $task): bool
    {
        if (in_array($task->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        return $task->forceFill([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ])->save();
    }
}
