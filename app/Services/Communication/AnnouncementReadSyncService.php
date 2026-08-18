<?php

namespace App\Services\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Models\Notification;
use App\Models\User;

class AnnouncementReadSyncService
{
    public function __construct(
        private readonly CommunicationHubService $hub,
    ) {}

    public function syncFromNotification(User $user, Notification $notification): void
    {
        $announcement = $this->resolveAnnouncement($notification);

        if (! $announcement?->isPublished()) {
            return;
        }

        if ($this->hub->hasRead($user, $announcement)) {
            return;
        }

        $this->hub->markRead($user, $announcement);
    }

    public function resolveAnnouncement(Notification $notification): ?MessageCenterAnnouncement
    {
        if (! $this->isAnnouncementNotification($notification)) {
            return null;
        }

        if (
            $notification->related_type === MessageCenterAnnouncement::class
            && $notification->related_id
        ) {
            return MessageCenterAnnouncement::query()->find($notification->related_id);
        }

        $slug = data_get($notification->data, 'action_route_params.announcement')
            ?? data_get($notification->action_link, 'params.announcement');

        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        return MessageCenterAnnouncement::query()->where('slug', $slug)->first();
    }

    private function isAnnouncementNotification(Notification $notification): bool
    {
        if ($notification->module === 'announcement') {
            return true;
        }

        if ($notification->related_type === MessageCenterAnnouncement::class) {
            return true;
        }

        if (data_get($notification->data, 'trigger') === 'announcement_published') {
            return true;
        }

        if (data_get($notification->data, 'action_route') === 'communications.show') {
            return true;
        }

        return data_get($notification->action_link, 'route') === 'communications.show';
    }
}
