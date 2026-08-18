<?php

namespace App\Services\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskUserAttributes;
use App\Services\Goals\GoalBridgeService;

class FnaGoalsBridge
{
    public function __construct(
        private readonly GoalBridgeService $goals,
    ) {}

    public function handleSubmitted(FnaRecord $fna, User $actor): void
    {
        $owner = $fna->owner;

        if (! $owner) {
            return;
        }

        $metrics = config('revenue-bridges.fna.submitted_metrics', []);
        $this->goals->syncMetrics($owner, $metrics);

        $activityKey = config('revenue-bridges.fna.activity_keys.submitted', 'fnas');
        $this->goals->logActivity(
            $owner,
            $activityKey,
            1,
            "FNA submitted for review — {$fna->client_name}.",
        );
    }

    public function handleApproved(FnaRecord $fna, User $actor): void
    {
        $owner = $fna->owner;

        if (! $owner) {
            return;
        }

        $metrics = config('revenue-bridges.fna.approved_metrics', []);
        $this->goals->syncMetrics($owner, $metrics);

        $activityKey = config('revenue-bridges.fna.activity_keys.approved', 'fnas');
        $this->goals->logActivity(
            $owner,
            $activityKey,
            1,
            "FNA approved — {$fna->client_name}.",
        );

        $this->createApprovedTask($fna, $actor, $owner);
    }

    private function createApprovedTask(FnaRecord $fna, User $actor, User $owner): void
    {
        $template = config('revenue-bridges.fna.tasks.approved');

        if (! is_array($template)) {
            return;
        }

        $title = str_replace('{client}', $fna->client_name, (string) ($template['title'] ?? ''));

        if (trim($title) === '') {
            return;
        }

        TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'FNA',
            $title,
            [
                'assignee_id' => $owner->id,
                'priority' => $template['priority'] ?? 'high',
                'status' => 'to_do',
                'related_module' => 'fna',
                'related_person' => $fna->client_name,
                'related_prospect_id' => $fna->prospect_id,
                'related_fna_id' => $fna->id,
                'due_date' => now()->addDays((int) ($template['offset_days'] ?? 2))->toDateString(),
            ],
            $template['description'] ?? null,
            $template['priority'] ?? null,
        ));
    }
}
