<?php

namespace App\Services\Communication;

use App\Models\AnnouncementAcknowledgement;
use App\Models\AnnouncementAnalyticsDaily;
use App\Models\AnnouncementBookmark;
use App\Models\AnnouncementCampaign;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementReaction;
use App\Models\BroadcastMessage;
use App\Models\MessageCenterAnnouncement;
use App\Models\MessageCenterAnnouncementRead;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BroadcastService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $audienceConfig
     * @return list<int>
     */
    public function previewAudienceIds(string $audienceType, array $audienceConfig = []): array
    {
        return $this->audience->resolve($this->normalizeAudienceType($audienceType), $audienceConfig)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function previewAudienceCount(string $audienceType, array $audienceConfig = []): int
    {
        return count($this->previewAudienceIds($audienceType, $audienceConfig));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $sender, array $data): BroadcastMessage
    {
        $audienceType = $this->normalizeAudienceType($data['audience_type'] ?? 'all');
        $audienceConfig = $data['audience_config'] ?? [];
        $recipientIds = $this->previewAudienceIds($audienceType, $audienceConfig);

        $broadcast = BroadcastMessage::query()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'sender_id' => $sender->id,
            'audience_type' => $audienceType,
            'audience_config' => $audienceConfig ?: null,
            'status' => 'sent',
            'priority' => $data['priority'] ?? 'important',
            'sent_at' => now(),
            'recipient_count' => count($recipientIds),
        ]);

        if ($recipientIds !== []) {
            $this->notifications->dispatch('announcement_published', [
                'recipients' => ['user_ids' => $recipientIds],
                'template_data' => [
                    'announcement_title' => $broadcast->title,
                    'user_name' => $sender->name,
                ],
                'priority' => config('communication-hub.priorities.'.($data['priority'] ?? 'important').'.notification', 'medium'),
                'module' => 'broadcast',
                'action_link' => [
                    'route' => 'communications.index',
                ],
                'queue' => config('notifications.queue', true),
            ]);
        }

        return $broadcast->fresh('sender');
    }

    /**
     * @return Collection<int, BroadcastMessage>
     */
    public function recent(int $limit = 10): Collection
    {
        return BroadcastMessage::query()
            ->with('sender')
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();
    }

    private function normalizeAudienceType(string $audienceType): string
    {
        $aliases = array_merge(
            config('communication-hub.legacy_audience_aliases', []),
            config('communication-hub.broadcast_audience_aliases', []),
        );

        return $aliases[$audienceType] ?? $audienceType;
    }
}
