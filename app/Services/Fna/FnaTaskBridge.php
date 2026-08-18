<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;
use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskUserAttributes;

class FnaTaskBridge
{
    /**
     * @param  array<string, mixed>  $template
     */
    public function createFromTemplate(FnaRecord $fna, User $actor, array $template, ?User $assignee = null): ?TaskUser
    {
        $title = $template['title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        $replacements = [
            '{client}' => $fna->client_name,
            '{trainee}' => $fna->owner?->name ?? 'Trainee',
        ];

        $title = str_replace(array_keys($replacements), array_values($replacements), $title);

        $assigneeId = $assignee?->id
            ?? (($template['assignee'] ?? null) === 'cfm' ? $fna->cfm_user_id : $fna->owner_user_id);

        if (! $assigneeId) {
            return null;
        }

        $offsetDays = (int) ($template['offset_days'] ?? 1);

        return TaskUser::create(TaskUserAttributes::forSystemTask(
            'FNA',
            $title,
            [
                'assignee_id' => $assigneeId,
                'priority' => $template['priority'] ?? 'medium',
                'status' => 'to_do',
                'related_module' => 'fna',
                'related_person' => $fna->client_name,
                'related_prospect_id' => $fna->prospect_id,
                'related_fna_id' => $fna->id,
                'due_date' => now()->addDays($offsetDays)->toDateString(),
            ],
            $template['description'] ?? null,
            $template['priority'] ?? null,
        ));
    }
}
