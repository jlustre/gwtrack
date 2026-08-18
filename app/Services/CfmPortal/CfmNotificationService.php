<?php



namespace App\Services\CfmPortal;



use App\Models\CfmNotification;

use App\Models\CfmProgressReport;

use App\Models\User;

use App\Services\Notifications\NotificationOrchestrator;



class CfmNotificationService

{

    public function __construct(

        private readonly CfmTraineeCenterService $centers,

        private readonly CfmSmsService $sms,

        private readonly NotificationOrchestrator $notifications,

    ) {}



    /**

     * @return array<string, mixed>|null

     */

    public function recentForTrainee(User $cfm, int $traineeId, int $limit = 8): ?array

    {

        if (! $this->centers->resolveTrainee($cfm, $traineeId)) {

            return null;

        }



        return CfmNotification::query()

            ->where('cfm_id', $cfm->id)

            ->where('trainee_id', $traineeId)

            ->latest()

            ->limit($limit)

            ->get()

            ->map(fn (CfmNotification $notification) => [

                'id' => $notification->id,

                'subject' => $notification->subject,

                'body' => $notification->body,

                'channel' => $notification->channel,

                'sent_at' => $notification->sent_at?->format('M j, Y g:i A'),

            ])

            ->values()

            ->all();

    }



    public function notifyTrainee(

        User $cfm,

        User $trainee,

        User $actor,

        string $subject,

        string $body,

        ?string $template = null,

        string $channel = 'in_app',

    ): CfmNotification {

        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {

            abort(403);

        }



        if ($channel === 'sms') {

            $this->dispatchSms($trainee, $body);

        }



        $notification = CfmNotification::query()->create([

            'cfm_id' => $cfm->id,

            'trainee_id' => $trainee->id,

            'template' => $template,

            'channel' => $channel,

            'subject' => $subject,

            'body' => $body,

            'sent_at' => now(),

            'created_by' => $actor->id,

        ]);

        if ($channel !== 'sms') {
            $this->notifications->dispatch('cfm_portal_notification', [
                'queue' => true,
                'sender' => $actor,
                'recipients' => [$trainee->id],
                'module' => 'fap',
                'priority' => 'medium',
                'related_user_id' => $trainee->id,
                'title' => $subject,
                'message' => $body,
                'template_data' => [
                    'title' => $subject,
                    'message' => $body,
                    'cfm_name' => $cfm->name,
                ],
                'action_link' => [
                    'route' => 'cfm.portal',
                    'params' => ['trainee' => $trainee->id],
                    'label' => 'Open CFM portal',
                ],
            ]);
        }

        return $notification;

    }



    public function notifyFromSmsTemplate(

        User $cfm,

        User $trainee,

        User $actor,

        string $templateKey,

        ?string $customBody = null,

    ): CfmNotification {

        $message = $this->sms->renderTemplate($templateKey, $cfm, $trainee, $customBody);



        return $this->notifyTrainee(

            $cfm,

            $trainee,

            $actor,

            $message['subject'],

            $message['body'],

            $templateKey,

            'sms',

        );

    }



    public function notifyProgressReport(User $cfm, User $trainee, User $actor, CfmProgressReport $report, string $channel = 'in_app'): CfmNotification

    {

        $payload = $report->payload;



        $subject = 'New progress report from your CFM';

        $body = sprintf(

            '%s shared a %s with you. Onboarding: %d%% · FAP: %d%% · Licensing: %d%% · Training: %d%%.',

            $cfm->name,

            strtolower($report->typeLabel()),

            $payload['progress']['onboarding'] ?? 0,

            $payload['progress']['fap'] ?? 0,

            $payload['progress']['licensing'] ?? 0,

            $payload['progress']['training'] ?? 0,

        );



        return $this->notifyTrainee(

            $cfm,

            $trainee,

            $actor,

            $subject,

            $body,

            'progress_report',

            $channel,

        );

    }



    private function dispatchSms(User $trainee, string $body): void
    {
        $result = $this->sms->send($trainee, $body);

        if (! $result['success']) {
            abort(422, $result['message']);
        }
    }
}


