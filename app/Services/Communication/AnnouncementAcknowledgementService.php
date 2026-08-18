<?php

namespace App\Services\Communication;

use App\Models\AnnouncementAcknowledgement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementReaction;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AnnouncementAcknowledgementService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
    ) {}

    /**
     * @return Collection<int, MessageCenterAnnouncement>
     */
    public function pendingCriticalFor(User $user): Collection
    {
        $ackedIds = AnnouncementAcknowledgement::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id');

        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->where('requires_acknowledgement', true)
            ->whereIn('priority', ['critical', 'emergency'])
            ->when($ackedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $ackedIds))
            ->with(['category', 'creator'])
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->values();
    }

    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     priority: string,
     *     published_at: string|null,
     *     audience_total: int,
     *     acknowledged_count: int,
     *     pending_count: int,
     *     completion_percent: float
     * }>
     */
    public function acknowledgementReport(): array
    {
        return MessageCenterAnnouncement::query()
            ->published()
            ->where('requires_acknowledgement', true)
            ->with(['category', 'creator'])
            ->orderByDesc('published_at')
            ->get()
            ->map(function (MessageCenterAnnouncement $announcement): array {
                $audienceIds = $this->audience->resolveUserIds($announcement);
                $audienceTotal = count($audienceIds);
                $acknowledgedCount = AnnouncementAcknowledgement::query()
                    ->where('announcement_id', $announcement->id)
                    ->when($audienceIds !== [], fn ($query) => $query->whereIn('user_id', $audienceIds))
                    ->count();
                $pendingCount = max(0, $audienceTotal - $acknowledgedCount);

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'slug' => $announcement->slug,
                    'priority' => $announcement->priority,
                    'category' => $announcement->category?->name,
                    'published_at' => $announcement->published_at?->toDateTimeString(),
                    'audience_total' => $audienceTotal,
                    'acknowledged_count' => $acknowledgedCount,
                    'pending_count' => $pendingCount,
                    'completion_percent' => $audienceTotal > 0
                        ? round(($acknowledgedCount / $audienceTotal) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{audience_total: int, acknowledged_count: int, pending_count: int, pending_users: list<array{id: int, name: string}>}
     */
    public function acknowledgementDetail(MessageCenterAnnouncement $announcement, int $pendingLimit = 25): array
    {
        if (! $announcement->requires_acknowledgement) {
            throw new InvalidArgumentException('Announcement does not require acknowledgement.');
        }

        $audienceIds = $this->audience->resolveUserIds($announcement);
        $acknowledgedIds = AnnouncementAcknowledgement::query()
            ->where('announcement_id', $announcement->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        $pendingIds = collect($audienceIds)->diff($acknowledgedIds)->values();

        $pendingUsers = User::query()
            ->whereIn('id', $pendingIds)
            ->orderBy('name')
            ->limit($pendingLimit)
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->values()
            ->all();

        return [
            'audience_total' => count($audienceIds),
            'acknowledged_count' => $acknowledgedIds->count(),
            'pending_count' => $pendingIds->count(),
            'pending_users' => $pendingUsers,
        ];
    }
}
