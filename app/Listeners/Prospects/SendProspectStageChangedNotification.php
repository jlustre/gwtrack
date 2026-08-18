<?php

namespace App\Listeners\Prospects;

use App\Events\Prospects\ProspectStageChanged;
use App\Models\ProspectFunnelStage;
use App\Services\Notifications\NotificationOrchestrator;

class SendProspectStageChangedNotification
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(ProspectStageChanged $event): void
    {
        $owner = $event->prospect->owner;

        if (! $owner || $owner->id === $event->actor->id) {
            return;
        }

        $stage = ProspectFunnelStage::query()->find($event->toStageId);
        $stageName = $stage?->name ?? 'a new stage';

        $this->notifications->dispatch('prospect_stage_changed', [
            'queue' => true,
            'sender' => $event->actor,
            'recipients' => [$owner->id],
            'module' => 'prospect',
            'priority' => 'info',
            'related' => ['type' => $event->prospect::class, 'id' => $event->prospect->id],
            'template_data' => [
                'prospect_name' => $event->prospect->displayName(),
                'stage_name' => $stageName,
            ],
            'action_link' => [
                'route' => 'team.prospects.records.show',
                'params' => ['prospect' => $event->prospect->id],
                'label' => 'View prospect',
            ],
        ]);
    }
}
