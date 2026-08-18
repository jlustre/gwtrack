<?php



namespace App\Notifications;



use App\Models\User;

use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Notification;



class RecommendCfmReminderNotification extends Notification

{

    use Queueable;



    public function __construct(

        private readonly User $newMember,

        private readonly ?User $agencyOwner = null,

    ) {}



    public function via(object $notifiable): array

    {

        return ['database'];

    }



    public function toArray(object $notifiable): array

    {

        $agencyOwnerName = $this->agencyOwner?->name ?? 'your agency owner';



        return [

            'trigger' => 'new_member_registration',

            'category' => 'Mentor Assignment',

            'title' => 'Recommend a CFM for '.$this->newMember->name,

            'message' => $this->newMember->name.' just registered under your sponsorship. You can recommend a Certified Field Mentor and remind '.$agencyOwnerName.' to assign one.',

            'member_id' => $this->newMember->id,

            'member_name' => $this->newMember->name,

            'agency_owner_name' => $agencyOwnerName,

            'action_route' => 'team.member',
            'action_route_params' => ['user' => $this->newMember->id],
            'action_url' => route('team.member', $this->newMember, false),

        ];

    }

}

