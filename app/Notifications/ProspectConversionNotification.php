<?php

namespace App\Notifications;

use App\Models\Prospect;
use App\Models\ProspectConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProspectConversionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Prospect $prospect,
        private readonly ProspectConversion $conversion,
        private readonly string $phase,
        private readonly ?string $invitationUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $prospectName = $this->prospect->displayName();
        $typeLabel = config(
            "prospects.conversion_types.{$this->conversion->conversion_type}",
            str($this->conversion->conversion_type)->replace('_', ' ')->title()->toString(),
        );

        if ($this->phase === 'completed') {
            $title = "{$prospectName} is now an {$typeLabel}";
            $message = "Registration completed for {$prospectName}. The prospect conversion has been finalized.";
        } else {
            $title = "{$typeLabel} conversion started for {$prospectName}";
            $message = match ($this->conversion->conversion_type) {
                'associate' => "A registration invitation was created for {$prospectName}. Share the link to complete associate conversion.",
                'client' => "Client conversion recorded for {$prospectName}".($this->conversion->policy_reference ? " (Policy: {$this->conversion->policy_reference})" : '.'),
                default => "Conversion initiated for {$prospectName}.",
            };
        }

        $data = [
            'trigger' => 'prospect_conversion',
            'category' => 'Prospect Management',
            'title' => $title,
            'message' => $message,
            'prospect_id' => $this->prospect->id,
            'prospect_name' => $prospectName,
            'conversion_type' => $this->conversion->conversion_type,
            'conversion_phase' => $this->phase,
            'action_route' => 'team.prospects.records.show',
            'action_route_params' => ['prospect' => $this->prospect->id],
            'action_url' => route('team.prospects.records.show', $this->prospect, false),
        ];

        if ($this->invitationUrl) {
            $data['invitation_url'] = $this->invitationUrl;
        }

        return $data;
    }
}
