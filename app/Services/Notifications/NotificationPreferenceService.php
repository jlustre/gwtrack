<?php

namespace App\Services\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\NotificationPreferenceDefault;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationPreferenceService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function matrixFor(User $user): Collection
    {
        $types = NotificationType::query()
            ->where('is_active', true)
            ->where('user_configurable', true)
            ->orderBy('sort_order')
            ->get();

        $channels = NotificationChannel::query()
            ->where('is_active', true)
            ->where('is_user_selectable', true)
            ->orderBy('sort_order')
            ->get();

        $saved = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (NotificationPreference $pref) => "{$pref->notification_type_id}:{$pref->notification_channel_id}");

        return $types->map(function (NotificationType $type) use ($user, $channels, $saved) {
            return [
                'type_id' => $type->id,
                'type_code' => $type->code,
                'type_name' => $type->name,
                'channels' => $channels->map(function (NotificationChannel $channel) use ($user, $type, $saved) {
                    $key = "{$type->id}:{$channel->id}";
                    $pref = $saved->get($key);
                    $defaults = $this->defaultsFor($user, $type->id, $channel->id);

                    return [
                        'channel_id' => $channel->id,
                        'channel_code' => $channel->code,
                        'channel_name' => $channel->name,
                        'enabled' => $pref?->enabled ?? $defaults['enabled'],
                        'frequency' => $pref?->frequency ?? $defaults['frequency'],
                    ];
                })->values()->all(),
            ];
        });
    }

    /**
     * @param  array<int, array{type_id: int, channel_id: int, enabled: bool, frequency?: string}>  $preferences
     */
    public function save(User $user, array $preferences): void
    {
        foreach ($preferences as $row) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type_id' => $row['type_id'],
                    'notification_channel_id' => $row['channel_id'],
                ],
                [
                    'enabled' => (bool) ($row['enabled'] ?? true),
                    'frequency' => $row['frequency'] ?? 'immediate',
                ],
            );
        }
    }

    public function isChannelEnabled(User $user, string $typeCode, string $channelCode): bool
    {
        $typeId = NotificationType::query()->where('code', $typeCode)->value('id');
        $channelId = NotificationChannel::query()->where('code', $channelCode)->value('id');

        if (! $typeId || ! $channelId) {
            return true;
        }

        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type_id', $typeId)
            ->where('notification_channel_id', $channelId)
            ->first();

        if ($pref) {
            return $pref->enabled;
        }

        return $this->defaultsFor($user, $typeId, $channelId)['enabled'];
    }

    /**
     * @return array{enabled: bool, frequency: string}
     */
    protected function defaultsFor(User $user, int $typeId, int $channelId): array
    {
        $role = $user->roles->first()?->name;

        if ($role) {
            $default = NotificationPreferenceDefault::query()
                ->where('role', $role)
                ->where('notification_type_id', $typeId)
                ->where('notification_channel_id', $channelId)
                ->first();

            if ($default) {
                return [
                    'enabled' => $default->enabled,
                    'frequency' => $default->frequency,
                ];
            }
        }

        return ['enabled' => true, 'frequency' => 'immediate'];
    }
}
