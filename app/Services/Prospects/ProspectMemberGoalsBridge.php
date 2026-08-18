<?php

namespace App\Services\Prospects;

use App\Events\Prospects\ProspectConverted;
use App\Models\MemberProductionEntry;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskUserAttributes;
use App\Services\Goals\GoalBridgeService;
use App\Services\MemberProductionService;

class ProspectMemberGoalsBridge
{
    public function __construct(
        private readonly GoalBridgeService $goals,
        private readonly MemberProductionService $production,
    ) {}

    public function handleConversion(ProspectConverted $event): void
    {
        $owner = $event->prospect->owner;

        if (! $owner) {
            return;
        }

        match ($event->conversion->conversion_type) {
            'associate' => $this->handleAssociate($event, $owner),
            'client' => $this->handleClient($event, $owner),
            default => null,
        };
    }

    private function handleAssociate(ProspectConverted $event, User $owner): void
    {
        $prospectName = $event->prospect->displayName();

        if ($event->phase === 'initiated') {
            $metrics = config('revenue-bridges.prospect.associate_initiated_metrics', []);
            $this->goals->syncMetrics($owner, $metrics);
            $this->goals->logActivity(
                $owner,
                'registrations',
                1,
                "Registration invitation sent to {$prospectName}.",
            );
            $this->createTask($owner, $event->actor, 'associate_initiated', [
                '{prospect}' => $prospectName,
            ], $event->prospect->id);

            return;
        }

        if ($event->phase !== 'completed') {
            return;
        }

        $member = $event->conversion->createdUser;
        $memberName = $member?->name ?? $prospectName;

        $metrics = config('revenue-bridges.prospect.associate_completed_metrics', []);
        $this->goals->syncMetrics($owner, $metrics);
        $this->goals->logActivity(
            $owner,
            'recruits',
            1,
            "{$memberName} completed registration and joined your team.",
        );

        $this->createTask($owner, $event->actor, 'associate_completed', [
            '{member}' => $memberName,
            '{prospect}' => $prospectName,
        ], $event->prospect->id);
    }

    private function handleClient(ProspectConverted $event, User $owner): void
    {
        if ($event->phase !== 'initiated') {
            return;
        }

        $prospectName = $event->prospect->displayName();
        $metrics = config('revenue-bridges.prospect.client_metrics', []);
        $this->goals->syncMetrics($owner, $metrics);
        $this->goals->logActivity(
            $owner,
            'applications',
            1,
            "Client conversion recorded for {$prospectName}.",
        );

        if (config('revenue-bridges.prospect.create_production_on_client_conversion', true)) {
            $this->recordClientProduction($owner, $event, $prospectName);
        }

        $this->createTask($owner, $event->actor, 'client_conversion', [
            '{prospect}' => $prospectName,
        ], $event->prospect->id);
    }

    private function recordClientProduction(User $owner, ProspectConverted $event, string $prospectName): void
    {
        $policyReference = $event->conversion->policy_reference;

        if (! filled($policyReference)) {
            return;
        }

        $exists = MemberProductionEntry::query()
            ->where('user_id', $owner->id)
            ->where('policy_reference', $policyReference)
            ->exists();

        if ($exists) {
            return;
        }

        $premium = (float) config('revenue-bridges.prospect.client_default_premium', 2500);

        $this->production->createForMember($owner, $event->actor, [
            'description' => "Client policy — {$prospectName}",
            'policy_reference' => $policyReference,
            'annual_premium' => $premium,
            'posted_at' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function createTask(
        User $owner,
        User $actor,
        string $templateKey,
        array $replacements,
        string $prospectId,
    ): void {
        $template = config("revenue-bridges.prospect.tasks.{$templateKey}");

        if (! is_array($template)) {
            return;
        }

        $title = str_replace(array_keys($replacements), array_values($replacements), (string) ($template['title'] ?? ''));

        if (trim($title) === '') {
            return;
        }

        TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            'Prospect Follow-Up',
            $title,
            [
                'assignee_id' => $owner->id,
                'priority' => $template['priority'] ?? 'medium',
                'status' => 'to_do',
                'related_module' => 'prospects',
                'related_person' => $replacements['{prospect}'] ?? $replacements['{member}'] ?? null,
                'related_prospect_id' => $prospectId,
                'due_date' => now()->addDays((int) ($template['offset_days'] ?? 1))->toDateString(),
            ],
            $template['description'] ?? null,
            $template['priority'] ?? null,
        ));
    }
}
