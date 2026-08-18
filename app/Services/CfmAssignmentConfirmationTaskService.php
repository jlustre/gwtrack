<?php

namespace App\Services;

use App\Models\MentorAssignment;
use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskUserAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CfmAssignmentConfirmationTaskService
{
    public const RELATED_MODULE = 'CFM Assignment';

    public function assignIfNeeded(MentorAssignment $assignment): ?TaskUser
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        if ($assignment->status !== 'pending') {
            return null;
        }

        $cfm = $assignment->mentor;
        $member = $assignment->apprentice;

        if (! $cfm || ! $member) {
            return null;
        }

        if ($this->openTasksForAssignment($assignment)->isNotEmpty()) {
            return null;
        }

        $title = $this->taskTitleFor($member->name);

        return TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'CFM Mentorship',
            $title,
            [
                'assignee_id' => $cfm->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => self::RELATED_MODULE,
                'related_person' => $member->name,
                'due_date' => now()->addDays(2)->toDateString(),
                'additional_notes' => $this->notesPayload($assignment->id),
            ],
            'Review and confirm this trainee assignment so onboarding and field apprenticeship can begin.',
            'high',
        ));
    }

    public function completeForAssignment(MentorAssignment $assignment): void
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        $this->openTasksForAssignment($assignment)
            ->each(fn (TaskUser $task): bool => $this->markCompleted($task));
    }

    public function taskTitleFor(string $memberName): string
    {
        return 'Confirm trainee assignment for '.$memberName;
    }

    public function isConfirmationTaskTitle(string $title): bool
    {
        return str_starts_with(strtolower(trim($title)), 'confirm trainee assignment for ');
    }

    public function mentorAssignmentIdFromTask(TaskUser $task): ?int
    {
        $notes = $task->additional_notes;

        if (blank($notes)) {
            return null;
        }

        $decoded = json_decode((string) $notes, true);

        if (is_array($decoded) && isset($decoded['mentor_assignment_id'])) {
            return (int) $decoded['mentor_assignment_id'];
        }

        if (preg_match('/mentor_assignment_id[:\s]+(\d+)/i', (string) $notes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @return Collection<int, TaskUser>
     */
    private function openTasksForAssignment(MentorAssignment $assignment): Collection
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        $slug = Str::slug($this->taskTitleFor($assignment->apprentice->name));

        return TaskUser::query()
            ->with('task')
            ->where('assignee_id', $assignment->mentor_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('id')
            ->get()
            ->filter(function (TaskUser $task) use ($assignment, $slug): bool {
                if ($this->mentorAssignmentIdFromTask($task) === (int) $assignment->id) {
                    return true;
                }

                if ($task->related_person !== $assignment->apprentice->name) {
                    return false;
                }

                if ($task->task?->slug === $slug) {
                    return true;
                }

                return $this->isConfirmationTaskTitle($task->displayTitle())
                    && str_contains(strtolower($task->displayTitle()), strtolower($assignment->apprentice->name));
            })
            ->values();
    }

    private function notesPayload(int $assignmentId): string
    {
        return json_encode([
            'mentor_assignment_id' => $assignmentId,
        ], JSON_THROW_ON_ERROR);
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
