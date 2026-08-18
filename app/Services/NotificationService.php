<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class NotificationService
{
    /**
     * Recipients payload stored on each notification row.
     *
     * {
     *   "user_ids": [1, 2],
     *   "roles": ["certified-field-mentor"]
     * }
     *
     * `user_ids` lists explicit targets; `roles` expands to all users with those roles.
     * One notification record is created per resolved user (notifiable morph).
     */
    public function send(array $options): Collection
    {
        $trigger = $this->resolveTrigger($options);
        $type = $this->resolveType($options, $trigger);
        $template = $this->resolveTemplate($trigger, $options);
        $tokens = $options['template_data'] ?? $options['tokens'] ?? [];
        $title = $options['title'] ?? $template?->renderSubject($tokens);
        $body = $options['body'] ?? $options['message'] ?? $template?->renderBody($tokens);
        $actionLink = $options['action_link'] ?? null;
        $sender = $this->resolveSender($options);
        $recipientIds = $this->resolveRecipientUserIds($options['recipients'] ?? []);
        $recipientsSnapshot = $this->buildRecipientsSnapshot($options['recipients'] ?? [], $recipientIds);

        if ($recipientIds === []) {
            throw new InvalidArgumentException('At least one notification recipient is required.');
        }

        $templateSnapshot = $template?->snapshot();
        $triggerCode = $trigger->code;
        $priority = $options['priority'] ?? 'info';
        $payload = $options['payload'] ?? [];

        $data = array_merge([
            'trigger' => $triggerCode,
            'title' => $title,
            'message' => $body,
            'category' => $type->name,
            'priority' => $priority,
        ], $payload);

        if ($actionLink) {
            if (! empty($actionLink['route'])) {
                $data['action_route'] = $actionLink['route'];
                $data['action_route_params'] = $actionLink['params'] ?? [];
            }

            if (! empty($actionLink['url'])) {
                $data['action_url'] = $actionLink['url'];
            } elseif (! empty($actionLink['route'])) {
                $data['action_url'] = route($actionLink['route'], $actionLink['params'] ?? [], false);
            }
        }

        return User::query()
            ->whereIn('id', $recipientIds)
            ->get()
            ->map(function (User $recipient) use (
                $type,
                $trigger,
                $sender,
                $recipientsSnapshot,
                $templateSnapshot,
                $actionLink,
                $data,
                $options,
                $priority,
            ): Notification {
                return Notification::query()->create([
                    'notification_type_id' => $type->id,
                    'trigger_id' => $trigger->id,
                    'sender_type' => $sender['type'],
                    'sender_user_id' => $sender['user_id'],
                    'recipients' => $recipientsSnapshot,
                    'notification_template' => $templateSnapshot,
                    'action_link' => $actionLink,
                    'priority' => $priority,
                    'module' => $options['module'] ?? null,
                    'related_type' => $options['related_type'] ?? null,
                    'related_id' => $options['related_id'] ?? null,
                    'related_user_id' => $options['related_user_id'] ?? null,
                    'metadata' => $options['metadata'] ?? null,
                    'type' => 'database',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $recipient->id,
                    'data' => $data,
                ]);
            });
    }

    /**
     * @param  array{
     *     unread?: bool|null,
     *     read?: bool|null,
     *     type_id?: int|null,
     *     type_code?: string|null,
     *     from?: \DateTimeInterface|string|null,
     *     to?: \DateTimeInterface|string|null,
     *     per_page?: int|null
     * }  $filters
     */
    public function inbox(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->userInboxQuery($user);

        if (array_key_exists('unread', $filters) && $filters['unread'] !== null) {
            $filters['unread'] ? $query->whereNull('read_at') : $query->whereNotNull('read_at');
        }

        if (array_key_exists('read', $filters) && $filters['read'] !== null) {
            $filters['read'] ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
        }

        if (! empty($filters['type_id'])) {
            $query->where('notification_type_id', $filters['type_id']);
        }

        if (! empty($filters['type_code'])) {
            $typeId = NotificationType::query()->where('code', $filters['type_code'])->value('id');

            if ($typeId) {
                $query->where('notification_type_id', $typeId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function unreadCount(User $user): int
    {
        return $this->userInboxQuery($user)->whereNull('read_at')->count();
    }

    public function markAsRead(User $user, string $notificationId): Notification
    {
        $notification = $this->userInboxQuery($user)->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        $sync = app(\App\Services\Communication\AnnouncementReadSyncService::class);

        $this->userInboxQuery($user)
            ->whereNull('read_at')
            ->get()
            ->each(fn (Notification $notification) => $sync->syncFromNotification($user, $notification));

        return $this->userInboxQuery($user)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function isRecipient(Notification $notification, User $user): bool
    {
        if (
            $notification->notifiable_type === User::class
            && (int) $notification->notifiable_id === (int) $user->id
        ) {
            return true;
        }

        $recipients = $notification->recipients ?? [];

        if (in_array($user->id, $recipients['user_ids'] ?? [], true)) {
            return true;
        }

        $roles = $recipients['roles'] ?? [];

        return $roles !== [] && $user->hasAnyRole($roles);
    }

    protected function userInboxQuery(User $user): Builder
    {
        return Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);
    }

    /**
     * @return list<int>
     */
    protected function resolveRecipientUserIds(mixed $recipients): array
    {
        if ($recipients instanceof User) {
            return [$recipients->id];
        }

        if (is_int($recipients)) {
            return [$recipients];
        }

        if (! is_array($recipients)) {
            return [];
        }

        if ($this->isListOfUsers($recipients)) {
            return collect($recipients)->pluck('id')->unique()->values()->all();
        }

        if ($this->isListOfInts($recipients)) {
            return array_values(array_unique($recipients));
        }

        $userIds = collect($recipients['user_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        $roleNames = collect($recipients['roles'] ?? [])
            ->filter(fn ($role) => filled($role))
            ->values()
            ->all();

        if ($roleNames !== []) {
            $roleUserIds = User::query()
                ->role($roleNames)
                ->pluck('id')
                ->all();

            $userIds = array_values(array_unique([...$userIds, ...$roleUserIds]));
        }

        return $userIds;
    }

    /**
     * @param  list<int>  $resolvedUserIds
     * @return array{user_ids: list<int>, roles: list<string>}
     */
    protected function buildRecipientsSnapshot(mixed $recipients, array $resolvedUserIds): array
    {
        if (is_array($recipients) && (isset($recipients['user_ids']) || isset($recipients['roles']))) {
            return [
                'user_ids' => array_values(array_unique(array_map('intval', $recipients['user_ids'] ?? $resolvedUserIds))),
                'roles' => array_values($recipients['roles'] ?? []),
            ];
        }

        return [
            'user_ids' => $resolvedUserIds,
            'roles' => [],
        ];
    }

    /**
     * @return array{type: string, user_id: int|null}
     */
    protected function resolveSender(array $options): array
    {
        $sender = $options['sender'] ?? 'system';

        if ($sender instanceof User) {
            return [
                'type' => 'user',
                'user_id' => $sender->id,
            ];
        }

        if ($sender === 'user') {
            $senderUser = $options['sender_user'] ?? null;

            if (! $senderUser instanceof User && ! empty($options['sender_user_id'])) {
                $senderUser = User::query()->find($options['sender_user_id']);
            }

            return [
                'type' => 'user',
                'user_id' => $senderUser?->id,
            ];
        }

        return [
            'type' => 'system',
            'user_id' => null,
        ];
    }

    protected function resolveTrigger(array $options): NotificationTrigger
    {
        if (! empty($options['trigger']) && $options['trigger'] instanceof NotificationTrigger) {
            return $options['trigger'];
        }

        if (! empty($options['trigger_id'])) {
            return NotificationTrigger::query()->findOrFail($options['trigger_id']);
        }

        if (! empty($options['trigger_code'])) {
            return NotificationTrigger::query()->where('code', $options['trigger_code'])->firstOrFail();
        }

        if (! empty($options['trigger']) && is_string($options['trigger'])) {
            return NotificationTrigger::query()->where('code', $options['trigger'])->firstOrFail();
        }

        throw new InvalidArgumentException('A notification trigger is required.');
    }

    protected function resolveType(array $options, NotificationTrigger $trigger): NotificationType
    {
        if (! empty($options['type']) && $options['type'] instanceof NotificationType) {
            return $options['type'];
        }

        if (! empty($options['type_id'])) {
            return NotificationType::query()->findOrFail($options['type_id']);
        }

        $typeCode = $options['type_code'] ?? (is_string($options['type'] ?? null) ? $options['type'] : null);

        if ($typeCode) {
            return NotificationType::query()->where('code', $typeCode)->firstOrFail();
        }

        return $trigger->type()->firstOrFail();
    }

    protected function resolveTemplate(NotificationTrigger $trigger, array $options): ?NotificationTemplate
    {
        if (! empty($options['template']) && $options['template'] instanceof NotificationTemplate) {
            return $options['template'];
        }

        if (! empty($options['template_id'])) {
            return NotificationTemplate::query()->findOrFail($options['template_id']);
        }

        return NotificationTemplate::query()
            ->where('notification_trigger_id', $trigger->id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    protected function isListOfUsers(array $values): bool
    {
        return $values !== [] && collect($values)->every(fn ($value) => $value instanceof User);
    }

    protected function isListOfInts(array $values): bool
    {
        return $values !== [] && collect($values)->every(fn ($value) => is_int($value));
    }
}
