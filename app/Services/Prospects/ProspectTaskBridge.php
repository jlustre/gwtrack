<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ProspectAppointment;
use App\Models\ProspectFollowUp;
use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskUserAttributes;

class ProspectTaskBridge
{
    /**
     * @param  array<string, mixed>  $template
     */
    public function createFromStageTemplate(Prospect $prospect, User $actor, array $template): ?TaskUser
    {
        $title = $template['title'] ?? $template['task'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        return $this->createTask(
            prospect: $prospect,
            title: $title,
            description: $template['description'] ?? $template['notes'] ?? null,
            priority: $template['priority'] ?? 'medium',
            dueDate: isset($template['offset_days'])
                ? now()->addDays((int) $template['offset_days'])->toDateString()
                : ($template['due_date'] ?? now()->addDay()->toDateString()),
            systemAssigned: true,
        );
    }

    public function createFromActivity(ProspectActivity $activity): ?TaskUser
    {
        if (! filled($activity->next_action)) {
            return null;
        }

        $activity->loadMissing(['prospect', 'user']);

        return $this->createFromFollowUp(
            followUp: new ProspectFollowUp([
                'prospect_id' => $activity->prospect_id,
                'assigned_user_id' => $activity->prospect->owner_id,
                'due_at' => $activity->next_follow_up_at ?? now()->addDay(),
                'priority' => 'medium',
                'notes' => $activity->next_action,
            ]),
            title: 'Follow up: '.$activity->prospect->displayName(),
            description: $activity->next_action,
        );
    }

    public function createFromFollowUp(ProspectFollowUp $followUp, ?string $title = null, ?string $description = null): ?TaskUser
    {
        $followUp->loadMissing(['prospect']);

        if (! $followUp->prospect) {
            return null;
        }

        return $this->createTask(
            prospect: $followUp->prospect,
            title: $title ?? 'Follow up: '.$followUp->prospect->displayName(),
            description: $description ?? $followUp->notes,
            priority: $followUp->priority ?? 'medium',
            dueDate: $followUp->due_at?->toDateString() ?? now()->addDay()->toDateString(),
            systemAssigned: true,
        );
    }

    public function createFromMissedAppointment(ProspectAppointment $appt): ?TaskUser
    {
        $appt->loadMissing(['prospect', 'owner', 'type']);

        return $this->createTask(
            prospect: $appt->prospect,
            title: 'Reschedule missed appointment: '.$appt->prospect->displayName(),
            description: trim(($appt->type?->name ? $appt->type->name.' — ' : '').($appt->purpose ?? 'Follow up after missed appointment.')),
            priority: 'high',
            dueDate: now()->addDay()->toDateString(),
            systemAssigned: true,
        );
    }

    public function createFromScheduledAppointment(
        Prospect $prospect,
        string $title,
        ?string $description,
        string $dueDate,
    ): ?TaskUser {
        return $this->createTask(
            prospect: $prospect,
            title: $title,
            description: $description,
            priority: 'medium',
            dueDate: $dueDate,
            systemAssigned: true,
        );
    }

    private function createTask(
        Prospect $prospect,
        string $title,
        ?string $description,
        string $priority,
        string $dueDate,
        bool $systemAssigned = false,
    ): TaskUser {
        $existing = TaskUser::query()
            ->where('assignee_id', $prospect->owner_id)
            ->where('related_prospect_id', $prospect->id)
            ->whereDate('due_date', $dueDate)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('task', fn ($query) => $query->where('title', $title))
            ->first();

        if ($existing) {
            return $existing;
        }

        $attributes = [
            'assignee_id' => $prospect->owner_id,
            'additional_notes' => $description,
            'priority' => in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium',
            'status' => 'to_do',
            'related_module' => 'prospects',
            'related_person' => $prospect->displayName(),
            'related_prospect_id' => $prospect->id,
            'due_date' => $dueDate,
        ];

        if ($systemAssigned) {
            return TaskUser::create(TaskUserAttributes::forSystemTask(
                'Prospect Follow-Up',
                $title,
                $attributes,
                $description,
                $priority,
            ));
        }

        return TaskUser::create(TaskUserAttributes::forTask(
            'Prospect Follow-Up',
            $title,
            $attributes,
            $description,
            $priority,
        ));
    }
}
