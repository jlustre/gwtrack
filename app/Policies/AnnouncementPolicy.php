<?php

namespace App\Policies;

use App\Models\MessageCenterAnnouncement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAnnouncements();
    }

    public function view(User $user, MessageCenterAnnouncement $announcement): bool
    {
        if (! $user->canViewAnnouncements()) {
            return false;
        }

        return app(\App\Services\Communication\AnnouncementAudienceResolver::class)
            ->userCanSee($user, $announcement);
    }

    public function create(User $user): bool
    {
        return $user->can('create announcements')
            || $user->can('manage announcements');
    }

    public function update(User $user, MessageCenterAnnouncement $announcement): bool
    {
        if ($user->can('edit announcements') || $user->can('manage announcements')) {
            return true;
        }

        return $announcement->created_by === $user->id && $announcement->status === 'draft';
    }

    public function publish(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return $user->can('publish announcements')
            || $user->can('manage announcements');
    }

    public function delete(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return $user->can('delete announcements')
            || $user->can('manage announcements');
    }
}
