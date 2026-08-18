<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\MentorAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

class CfmTraineeChecklistService
{
    public function __construct(private readonly ChecklistService $checklists) {}

    public function ensureAssignmentAccess(MentorAssignment $assignment, User $actingUser): void
    {
        $this->checklists->ensureAssignmentAccess($assignment, $actingUser);
    }

    /**
     * @return array{
     *     assignment: MentorAssignment,
     *     trainee: User,
     *     stats: array<string, int|float>,
     *     phases: Collection<int, array<string, mixed>>
     * }
     */
    public function checklistForAssignment(MentorAssignment $assignment): array
    {
        return $this->checklists->mentoringChecklistForAssignment($assignment);
    }

    /**
     * @return array<string, mixed>
     */
    public function checklistJsonForAssignment(MentorAssignment $assignment): array
    {
        $payload = $this->checklistForAssignment($assignment);
        $trainee = $payload['trainee'];

        return [
            'trainee' => [
                'name' => $trainee->name,
                'rank' => $trainee->rank?->code ?? '—',
            ],
            'stats' => $payload['stats'],
            'phases' => $payload['phases']->map(function (array $phase) {
                return [
                    'phase_number' => $phase['phase_number'],
                    'phase_title' => $phase['phase_title'],
                    'phase_target' => $phase['phase_target'],
                    'total' => $phase['total'],
                    'completed' => $phase['completed'],
                    'percent' => $phase['percent'],
                    'sections' => collect($phase['sections'])->map(function (array $section) {
                        return [
                            'title' => $section['title'],
                            'items' => collect($section['items'])->map(function (array $item) {
                                return [
                                    'id' => $item['id'],
                                    'title' => $item['title'],
                                    'is_required' => $item['is_required'],
                                    'is_completed' => $item['is_completed'],
                                    'completed_at' => $item['completed_at']?->toDateString(),
                                    'notes' => $item['notes'],
                                    'action_url' => $item['action_url'] ?? null,
                                    'action_label' => $item['action_label'] ?? null,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function updateProgress(
        MentorAssignment $assignment,
        Checklist $item,
        User $actingUser,
        bool $completed,
        ?string $notes = null,
    ): ChecklistProgress {
        return $this->checklists->updateMentoringProgress($assignment, $item, $actingUser, $completed, $notes);
    }

    /**
     * @param  Collection<int, int>  $assignmentIds
     * @return array<int, int>
     */
    public function progressPercentsForAssignments(Collection $assignmentIds): array
    {
        return $this->checklists->mentoringProgressPercentsForAssignments($assignmentIds);
    }
}
