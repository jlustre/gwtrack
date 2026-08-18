<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignCfmReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly User $newMember) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'new_member_registration',
            'category' => 'Mentor Assignment',
            'title' => 'Assign a CFM for '.$this->newMember->name,
            'message' => $this->newMember->name.' just registered. Assign a Certified Field Mentor so onboarding can begin.',
            'member_id' => $this->newMember->id,
            'member_name' => $this->newMember->name,
            'action_route' => 'team.cfms',
            'action_url' => route('team.cfms', [], false),
        ];
    }
}
