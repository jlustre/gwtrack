<?php

namespace App\Services\Notifications;

use App\Models\Checklist;
use App\Models\User;
use App\Services\MemberUplineService;

class NotificationRecipientResolver
{
    public function __construct(
        private readonly MemberUplineService $memberUpline,
    ) {}

    /**
     * Resolve user IDs who should review a submitted checklist item.
     *
     * @return list<int>
     */
    public function checklistReviewers(User $member, Checklist $checklist): array
    {
        $member->loadMissing(['sponsor', 'team.leader']);
        $checklist->loadMissing('type');

        $parties = collect(explode(',', (string) $checklist->notified_parties))
            ->map(fn (string $party) => strtoupper(trim($party)))
            ->filter()
            ->values();

        $userIds = [];

        if ($parties->contains('SP') && $member->sponsor_id) {
            $userIds[] = (int) $member->sponsor_id;
        }

        if ($parties->contains('CFM') && $member->mentor_id) {
            $userIds[] = (int) $member->mentor_id;
        }

        if ($parties->contains('AO')) {
            $agencyOwner = $this->memberUpline->agencyOwner($member);

            if ($agencyOwner) {
                $userIds[] = $agencyOwner->id;
            }
        }

        if ($parties->contains('TL') && $member->team?->leader_id) {
            $userIds[] = (int) $member->team->leader_id;
        }

        if ($parties->contains('TR') && $member->team_id) {
            $trainerIds = User::query()
                ->where('team_id', $member->team_id)
                ->role('trainer')
                ->pluck('id')
                ->all();

            $userIds = [...$userIds, ...$trainerIds];
        }

        return array_values(array_unique(array_filter($userIds)));
    }

    /**
     * @return list<int>
     */
    public function conversationRecipients(User $sender, int $conversationId): array
    {
        return \App\Models\ConversationMember::query()
            ->where('conversation_id', $conversationId)
            ->whereNull('left_at')
            ->where('user_id', '!=', $sender->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
