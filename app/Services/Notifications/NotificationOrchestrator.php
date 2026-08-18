<?php



namespace App\Services\Notifications;



use App\Jobs\Notifications\SendNotificationJob;

use App\Models\Notification;

use App\Models\NotificationDeliveryLog;

use App\Models\NotificationTemplate;

use App\Models\NotificationTrigger;

use App\Models\NotificationType;

use App\Models\User;

use App\Services\NotificationService;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Illuminate\Support\Collection;

use Illuminate\Support\Str;

use InvalidArgumentException;



class NotificationOrchestrator extends NotificationService

{

    public function __construct(

        private readonly NotificationChannelDispatcher $channelDispatcher,

        private readonly NotificationInsightService $insights,

    ) {}



    /**

     * Queue or immediately deliver a notification by trigger code.

     *

     * @param  array<string, mixed>  $context

     */

    public function dispatch(string $triggerCode, array $context = []): Collection

    {

        $shouldQueue = $context['queue'] ?? config('notifications.queue', true);



        if ($shouldQueue) {

            SendNotificationJob::dispatch($triggerCode, $context);



            return collect();

        }



        return $this->deliver(array_merge($context, ['trigger_code' => $triggerCode, 'queue' => false]));

    }



    /**

     * @param  array<string, mixed>  $options

     */

    public function deliver(array $options): Collection

    {

        $triggerCode = $options['trigger_code']

            ?? (is_string($options['trigger'] ?? null) ? $options['trigger'] : null);



        if (! $triggerCode) {

            throw new InvalidArgumentException('A notification trigger code is required.');

        }



        $options['trigger_code'] = $triggerCode;

        $options = $this->normalizeDeliveryOptions($options);

        try {
            $this->resolveTrigger($options);
        } catch (ModelNotFoundException|InvalidArgumentException) {
            return collect();
        }

        $channels = $this->resolveChannels($options);

        $notifications = collect();



        if (in_array('in_app', $channels, true)) {

            $notifications = parent::send($options);

            $this->logInAppDeliveries($notifications, $triggerCode);

            $this->enrichNotifications($notifications);

        }



        $typeCode = $this->resolveTypeCode($options);

        $priority = $options['priority'] ?? 'info';

        $template = $this->resolveTemplateForOptions($options);

        $tokens = $options['template_data'] ?? $options['tokens'] ?? [];



        if (in_array('email', $channels, true)) {

            $this->deliverExternalChannel('email', $options, $notifications, $triggerCode, $typeCode, $priority, [

                'mail' => $options['mail'] ?? $this->buildMailFromOptions($options),

            ]);

        }



        if (in_array('sms', $channels, true)) {

            $this->deliverExternalChannel('sms', $options, $notifications, $triggerCode, $typeCode, $priority, [

                'body' => $this->buildSmsFromOptions($options, $template, $tokens),

            ]);

        }



        if (in_array('push', $channels, true)) {

            $this->deliverExternalChannel('push', $options, $notifications, $triggerCode, $typeCode, $priority, [

                'title' => $this->buildPushTitleFromOptions($options, $template, $tokens),

                'body' => $this->buildPushBodyFromOptions($options, $template, $tokens),

            ]);

        }



        return $notifications;

    }



    /**

     * @param  array<string, mixed>  $options

     * @return array<string, mixed>

     */

    private function normalizeDeliveryOptions(array $options): array

    {

        $payload = $options['payload'] ?? [];

        $priority = $options['priority'] ?? 'info';



        if (! empty($options['title'])) {

            $payload['title'] ??= $options['title'];

        }



        if (! empty($options['message'])) {

            $payload['message'] ??= $options['message'];

        }



        if (! empty($options['body'])) {

            $payload['message'] ??= $options['body'];

        }



        $options['payload'] = $payload;

        $options['priority'] = $priority;



        if (! empty($options['related']) && is_array($options['related'])) {

            $options['related_type'] = $options['related']['type'] ?? null;

            $options['related_id'] = $options['related']['id'] ?? null;

        }



        return $options;

    }



    /**

     * @param  array<string, mixed>  $options

     * @return list<string>

     */

    private function resolveChannels(array $options): array

    {

        if (! empty($options['channels']) && is_array($options['channels'])) {

            return collect($options['channels'])

                ->map(fn (string $channel) => $channel === 'database' ? 'in_app' : $channel)

                ->unique()

                ->values()

                ->all();

        }



        $priority = $options['priority'] ?? 'info';



        if (in_array($priority, config('notifications.critical_priorities', []), true)) {

            return collect(config('notifications.critical_channels', ['in_app', 'email']))

                ->when(config('notifications.sms.enabled', false)

                    && in_array($priority, config('notifications.critical_sms_priorities', ['critical']), true), fn ($collection) => $collection->push('sms'))

                ->unique()

                ->values()

                ->all();

        }



        $template = $this->resolveTemplateForOptions($options);

        $templateChannels = collect($template?->channels ?? ['in_app'])

            ->map(fn (string $channel) => $channel === 'database' ? 'in_app' : $channel)

            ->all();



        return $templateChannels !== [] ? $templateChannels : config('notifications.default_channels', ['in_app']);

    }



    /**

     * @param  array<string, mixed>  $options

     */

    private function resolveTemplateForOptions(array $options): ?NotificationTemplate

    {

        try {

            $trigger = $this->resolveTrigger($options);



            return NotificationTemplate::query()

                ->where('notification_trigger_id', $trigger->id)

                ->where('is_default', true)

                ->where('is_active', true)

                ->first();

        } catch (ModelNotFoundException|InvalidArgumentException) {

            return null;

        }

    }



    /**

     * @param  array<string, mixed>  $options

     */

    private function resolveTypeCode(array $options): string

    {

        try {

            $trigger = $this->resolveTrigger($options);

            $type = NotificationType::query()->find($trigger->notification_type_id);



            return $type?->code ?? 'system';

        } catch (InvalidArgumentException) {

            return 'system';

        }

    }



    /**

     * @param  array<string, mixed>  $options

     * @param  array<string, mixed>  $payload

     */

    private function deliverExternalChannel(

        string $channel,

        array $options,

        Collection $notifications,

        string $triggerCode,

        string $typeCode,

        string $priority,

        array $payload,

    ): void {

        if ($channel === 'email' && empty($payload['mail'])) {

            return;

        }



        if (in_array($channel, ['sms', 'push'], true) && blank($payload['body'] ?? null)) {

            return;

        }



        $recipientIds = $this->resolveRecipientUserIds($options['recipients'] ?? []);



        if ($recipientIds === []) {

            return;

        }



        $users = User::query()->whereIn('id', $recipientIds)->get();



        foreach ($users as $index => $user) {

            $notificationId = $notifications->get($index)?->id;

            $this->channelDispatcher->send(

                $channel,

                $user,

                $payload,

                $typeCode,

                $priority,

                $triggerCode,

                $notificationId,

            );

        }

    }



    /**

     * @param  array<string, mixed>  $options

     * @return array<string, mixed>|null

     */

    private function buildMailFromOptions(array $options): ?array

    {

        if (! empty($options['mail']) && is_array($options['mail'])) {

            return $options['mail'];

        }



        $title = $options['title'] ?? null;

        $message = $options['message'] ?? $options['body'] ?? null;



        if (! $title && ! $message) {

            return null;

        }



        $actionLink = $options['action_link'] ?? [];

        $actionUrl = $actionLink['url'] ?? null;



        if (! $actionUrl && ! empty($actionLink['route'])) {

            $actionUrl = route($actionLink['route'], $actionLink['params'] ?? [], absolute: true);

        }



        return [

            'subject' => $title ?? config('app.name').' notification',

            'lines' => array_filter([$message]),

            'action_text' => $actionLink['label'] ?? 'View',

            'action_url' => $actionUrl,

        ];

    }



    /**

     * @param  array<string, mixed>  $options

     * @param  array<string, mixed>  $tokens

     */

    private function buildSmsFromOptions(array $options, ?NotificationTemplate $template, array $tokens): ?string

    {

        if (! empty($options['sms_body'])) {

            return (string) $options['sms_body'];

        }



        if ($template?->sms_body) {

            return $template->renderSmsBody($tokens);

        }



        $message = $options['message'] ?? $options['body'] ?? data_get($options, 'payload.message');



        return $message ? Str::limit((string) $message, (int) config('notifications.sms.max_length', 160), '') : null;

    }



    /**

     * @param  array<string, mixed>  $options

     * @param  array<string, mixed>  $tokens

     */

    private function buildPushTitleFromOptions(array $options, ?NotificationTemplate $template, array $tokens): ?string

    {

        if (! empty($options['push_title'])) {

            return (string) $options['push_title'];

        }



        if ($template?->push_title) {

            return $template->renderPushTitle($tokens);

        }



        return $options['title'] ?? data_get($options, 'payload.title') ?? config('app.name');

    }



    /**

     * @param  array<string, mixed>  $options

     * @param  array<string, mixed>  $tokens

     */

    private function buildPushBodyFromOptions(array $options, ?NotificationTemplate $template, array $tokens): ?string

    {

        if (! empty($options['push_body'])) {

            return (string) $options['push_body'];

        }



        if ($template?->push_body) {

            return $template->renderPushBody($tokens);

        }



        $message = $options['message'] ?? $options['body'] ?? data_get($options, 'payload.message');



        return $message ? Str::limit((string) $message, 200, '') : null;

    }



    /**

     * @param  Collection<int, Notification>  $notifications

     */

    private function logInAppDeliveries(Collection $notifications, string $triggerCode): void

    {

        foreach ($notifications as $notification) {

            NotificationDeliveryLog::query()->create([

                'notification_id' => $notification->id,

                'user_id' => $notification->notifiable_id,

                'trigger_code' => $triggerCode,

                'channel' => 'in_app',

                'status' => 'sent',

                'attempted_at' => now(),

                'delivered_at' => now(),

            ]);

        }

    }



    /**

     * @param  Collection<int, Notification>  $notifications

     */

    private function enrichNotifications(Collection $notifications): void

    {

        foreach ($notifications as $notification) {

            $this->insights->enrich($notification);

        }

    }

}

