<?php

namespace App\Listeners\Prospects;

use App\Events\Prospects\ProspectConverted;
use App\Services\Notifications\NotificationOrchestrator;

class SendProspectConversionNotification
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(ProspectConverted $event): void
    {
        $owner = $event->prospect->owner;

        if (! $owner) {
            return;
        }

        $prospectName = $event->prospect->displayName();
        $typeLabel = config(
            "prospects.conversion_types.{$event->conversion->conversion_type}",
            str($event->conversion->conversion_type)->replace('_', ' ')->title()->toString(),
        );

        if ($event->phase === 'completed') {
            $title = "{$prospectName} is now an {$typeLabel}";
            $message = "Registration completed for {$prospectName}. The prospect conversion has been finalized.";
        } else {
            $title = "{$typeLabel} conversion started for {$prospectName}";
            $message = match ($event->conversion->conversion_type) {
                'associate' => "A registration invitation was created for {$prospectName}. Share the link to complete associate conversion.",
                'client' => "Client conversion recorded for {$prospectName}".($event->conversion->policy_reference ? " (Policy: {$event->conversion->policy_reference})" : '.'),
                default => "Conversion initiated for {$prospectName}.",
            };
        }

        $payload = [
            'prospect_id' => $event->prospect->id,
            'prospect_name' => $prospectName,
            'conversion_type' => $event->conversion->conversion_type,
            'conversion_phase' => $event->phase,
            'category' => 'Prospect Management',
        ];

        $invitationUrl = $event->phase === 'initiated' && $event->invitation
            ? $event->invitation->invitationUrl()
            : null;

        if ($invitationUrl) {
            $payload['invitation_url'] = $invitationUrl;
        }

        $this->notifications->dispatch('prospect_conversion', [
            'queue' => true,
            'recipients' => [$owner->id],
            'module' => 'prospect',
            'priority' => 'medium',
            'related' => ['type' => $event->prospect::class, 'id' => $event->prospect->id],
            'title' => $title,
            'message' => $message,
            'template_data' => [
                'message' => $message,
                'prospect_name' => $prospectName,
            ],
            'payload' => $payload,
            'action_link' => [
                'route' => 'team.prospects.records.show',
                'params' => ['prospect' => $event->prospect->id],
                'label' => 'View prospect',
            ],
        ]);
    }
}
