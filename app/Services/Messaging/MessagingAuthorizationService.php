<?php

namespace App\Services\Messaging;

use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\DownlineHierarchyService;

class MessagingAuthorizationService
{
    public function __construct(private readonly DownlineHierarchyService $hierarchy) {}

    public function canUseMessaging(User $user): bool
    {
        return ! $user->isMessagingSuspended();
    }

    public function canMessage(User $sender, User $recipient): bool
    {
        if (! $this->canUseMessaging($sender)) {
            return false;
        }

        if ((int) $sender->id === (int) $recipient->id) {
            return false;
        }

        if ($sender->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ($sender->hasRole('agency-owner') && $this->hierarchy->canViewMember($sender, $recipient)) {
            return true;
        }

        if ($this->hierarchy->canViewMember($sender, $recipient)) {
            return true;
        }

        if ($this->hierarchy->canViewMember($recipient, $sender)) {
            return true;
        }

        if ((int) $recipient->sponsor_id === (int) $sender->id || (int) $sender->sponsor_id === (int) $recipient->id) {
            return true;
        }

        if ((int) $recipient->mentor_id === (int) $sender->id || (int) $sender->mentor_id === (int) $recipient->id) {
            return true;
        }

        return MentorAssignment::query()
            ->where(function ($query) use ($sender, $recipient): void {
                $query->where(fn ($q) => $q->where('mentor_id', $sender->id)->where('apprentice_id', $recipient->id))
                    ->orWhere(fn ($q) => $q->where('mentor_id', $recipient->id)->where('apprentice_id', $sender->id));
            })
            ->where('status', 'active')
            ->exists();
    }

    public function canSendBroadcast(User $user): bool
    {
        return $user->hasAnyPermission(['send message broadcasts', 'manage announcements'])
            && $user->hasAnyRole(['super-admin', 'admin', 'agency-owner']);
    }

    public function canViewAnalytics(User $user): bool
    {
        return $user->hasAnyPermission(['view communication analytics', 'view analytics']);
    }
}
