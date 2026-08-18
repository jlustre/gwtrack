<?php

namespace App\Services;

use App\Models\TaskUser;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use App\Support\SystemTaskAssignor;
use App\Support\TaskUserAttributes;
use Illuminate\Support\Str;

class NewMemberValidatedTaskService
{
    public const PROFILE_TASK_SLUG = 'complete-your-member-profile';

    public const PROFILE_TASK_TITLE = 'Complete your member profile';

    public const PROFILE_TASK_DESCRIPTION = 'Fill in the required profile fields so your team can support your onboarding.';

    public function __construct(
        private readonly NewMemberOnboardingTaskService $onboardingTasks,
        private readonly ProfileCompletionService $profileCompletion,
        private readonly MemberUplineService $memberUpline,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function assignAll(User $member): void
    {
        $member->loadMissing(['profile', 'sponsor', 'roles']);

        if (! $member->hasRole('member')) {
            return;
        }

        $this->onboardingTasks->assignIfNeeded($member);
        $this->assignProfileCompletionTaskIfNeeded($member);

        if ($member->sponsor_id) {
            $this->assignSponsorWelcomeCallTaskIfNeeded($member);
        }

        $agencyOwner = $this->memberUpline->agencyOwner($member);

        if ($agencyOwner && ! $member->mentor_id) {
            $this->assignAgencyOwnerCfmTaskIfNeeded($member, $agencyOwner);
        }
    }

    public function completeProfileTaskIfOpen(User $member): void
    {
        if (! $this->profileCompletion->isComplete($member)) {
            return;
        }

        $this->openTasksFor($member, self::PROFILE_TASK_SLUG)
            ->each(fn (TaskUser $task): bool => $this->markCompleted($task));
    }

    public function completeCfmAssignmentTasksIfOpen(User $member): void
    {
        $this->completeCfmAssignmentTasksForMember($member);
    }

    public function completeCfmAssignmentTasksForMember(User $member): void
    {
        $this->openCfmAssignmentTasksForMember($member)
            ->each(fn (TaskUser $task): bool => $this->markCompleted($task));
    }

    private function assignProfileCompletionTaskIfNeeded(User $member): ?TaskUser
    {
        if ($this->profileCompletion->isComplete($member)) {
            return null;
        }

        if ($this->openTasksFor($member, self::PROFILE_TASK_SLUG)->isNotEmpty()) {
            return null;
        }

        $taskUser = TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'Personal',
            self::PROFILE_TASK_TITLE,
            [
                'assignee_id' => $member->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => 'Profile',
                'related_person' => $member->name,
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            self::PROFILE_TASK_DESCRIPTION,
            'high',
        ));

        $this->notifyTaskAssigned($member, $taskUser, 'Open Profile', 'profile.edit');

        return $taskUser;
    }

    private function assignSponsorWelcomeCallTaskIfNeeded(User $member): ?TaskUser
    {
        $sponsor = $member->sponsor;

        if (! $sponsor) {
            return null;
        }

        $title = 'Welcome '.$member->name.' with a phone call';

        if ($this->openTasksForAssignee($sponsor, Str::slug($title), $member->name)->isNotEmpty()) {
            return null;
        }

        $taskUser = TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'Onboarding',
            $title,
            [
                'assignee_id' => $sponsor->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => 'Onboarding',
                'related_person' => $member->name,
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            'Call your new team member to introduce yourself, review expectations, and answer their first questions.',
            'high',
        ));

        $this->notifyTaskAssigned(
            $sponsor,
            $taskUser,
            'View member',
            'team.member',
            ['user' => $member->id],
        );

        return $taskUser;
    }

    private function assignAgencyOwnerCfmTaskIfNeeded(User $member, User $agencyOwner): ?TaskUser
    {
        $title = 'Assign a CFM to '.$member->name;

        if ($this->openTasksForAssignee($agencyOwner, Str::slug($title), $member->name)->isNotEmpty()) {
            return null;
        }

        $taskUser = TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'Assign a CFM',
            $title,
            [
                'assignee_id' => $agencyOwner->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => 'Team',
                'related_person' => $member->name,
                'due_date' => now()->addDays(2)->toDateString(),
            ],
            'Match this new member with a Certified Field Mentor so onboarding and field apprenticeship can begin.',
            'high',
        ));

        $this->notifyTaskAssigned(
            $agencyOwner,
            $taskUser,
            'Assign CFM',
            'team.cfms',
        );

        return $taskUser;
    }

    /**
     * @return \Illuminate\Support\Collection<int, TaskUser>
     */
    private function openTasksFor(User $member, string $taskSlug): \Illuminate\Support\Collection
    {
        return TaskUser::query()
            ->with('task')
            ->where('assignee_id', $member->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('id')
            ->get()
            ->filter(fn (TaskUser $task): bool => $task->task?->slug === $taskSlug)
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, TaskUser>
     */
    private function openTasksForAssignee(User $assignee, string $taskSlug, string $relatedPerson): \Illuminate\Support\Collection
    {
        return TaskUser::query()
            ->with('task')
            ->where('assignee_id', $assignee->id)
            ->where('related_person', $relatedPerson)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('id')
            ->get()
            ->filter(fn (TaskUser $task): bool => $task->task?->slug === $taskSlug
                || str_contains(strtolower($task->displayTitle()), strtolower($relatedPerson)))
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, TaskUser>
     */
    private function openCfmAssignmentTasksForMember(User $member): \Illuminate\Support\Collection
    {
        $slug = Str::slug('Assign a CFM to '.$member->name);

        return TaskUser::query()
            ->with('task')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('related_person', $member->name)
            ->latest('id')
            ->get()
            ->filter(function (TaskUser $task) use ($member, $slug): bool {
                if ($task->task?->slug === $slug) {
                    return true;
                }

                $title = strtolower($task->displayTitle());

                return str_contains($title, 'assign a cfm')
                    && str_contains($title, strtolower($member->name));
            })
            ->values();
    }

    private function notifyTaskAssigned(
        User $recipient,
        TaskUser $taskUser,
        string $actionLabel,
        string $routeName,
        array $routeParams = [],
    ): void {
        $this->notifications->dispatch('task_assigned', [
            'queue' => true,
            'recipients' => [$recipient->id],
            'module' => 'task',
            'priority' => 'high',
            'related' => ['type' => TaskUser::class, 'id' => $taskUser->id],
            'related_user_id' => $recipient->id,
            'template_data' => [
                'task_name' => $taskUser->displayTitle(),
                'cfm_name' => SystemTaskAssignor::NAME,
                'deadline' => $taskUser->due_date?->format('M j, Y') ?? 'Soon',
            ],
            'payload' => [
                'category' => $taskUser->categoryName(),
                'task_id' => $taskUser->id,
            ],
            'action_link' => [
                'route' => $routeName,
                'params' => $routeParams,
                'label' => $actionLabel,
            ],
        ]);
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
