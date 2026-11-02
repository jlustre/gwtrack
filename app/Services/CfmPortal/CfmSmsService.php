<?php



namespace App\Services\CfmPortal;



use App\Models\User;

use App\Services\Notifications\Transports\SmsTransport;



class CfmSmsService

{

    public function __construct(

        private readonly SmsTransport $sms,

    ) {}



    public function isEnabled(): bool

    {

        return (bool) config('cfm-portal.sms.enabled', false)

            || (bool) config('notifications.sms.enabled', false);

    }



    /**

     * @return array{success: bool, message: string, driver: string}

     */

    public function send(User $recipient, string $body, ?string $from = null): array

    {

        $phone = $recipient->profile?->phone;



        if (! $phone) {

            return [

                'success' => false,

                'message' => 'Trainee does not have a phone number on file.',

                'driver' => (string) config('notifications.sms.driver', 'log'),

            ];

        }



        if (! $this->isEnabled()) {

            return [

                'success' => false,

                'message' => 'SMS delivery is disabled.',

                'driver' => (string) config('notifications.sms.driver', 'log'),

            ];

        }



        $fromLabel = $from ?? config('cfm-portal.sms.from', config('notifications.sms.from', 'GWTracks'));



        return $this->sms->send($phone, $body, $fromLabel);

    }



    public function renderTemplate(string $templateKey, User $cfm, User $trainee, ?string $customBody = null): array

    {

        $templates = config('cfm-portal.sms_templates', []);

        $template = $templates[$templateKey] ?? $templates['custom'] ?? [

            'subject' => 'Message from your CFM',

            'body' => $customBody ?? '',

        ];



        $body = $customBody !== null && $customBody !== ''

            ? $customBody

            : ($template['body'] ?? '');



        $replacements = [

            '{trainee}' => $trainee->name,

            '{cfm}' => $cfm->name,

        ];



        return [

            'subject' => str_replace(array_keys($replacements), array_values($replacements), $template['subject'] ?? 'Message from your CFM'),

            'body' => str_replace(array_keys($replacements), array_values($replacements), $body),

        ];

    }



    /**

     * @return list<array{key: string, label: string}>

     */

    public function templateOptions(): array

    {

        return collect(config('cfm-portal.sms_templates', []))

            ->map(fn (array $template, string $key) => [

                'key' => $key,

                'label' => $template['label'] ?? ucfirst(str_replace('_', ' ', $key)),

            ])

            ->values()

            ->all();

    }

}


