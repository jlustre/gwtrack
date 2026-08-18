<?php

namespace App\Services\Communication;

use App\Models\Badge;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecognitionService
{
    public function __construct(
        private readonly CommunicationHubService $hub,
        private readonly CommunicationSectionService $sections,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function wallFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->sections->feedByCategory($user, 'recognition', $filters, $perPage);
    }

    /**
     * @return list<array{code: string, label: string, badge_slug: string|null}>
     */
    public function templates(): array
    {
        return collect(config('communication-hub.recognition_templates', []))
            ->map(fn (array $template, string $code) => [
                'code' => $code,
                'label' => $template['label'],
                'badge_slug' => $template['badge_slug'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{title: string, summary: string, body: string, badge_slug: string|null, recognition_type: string}
     */
    public function renderTemplate(string $templateCode, User $honoree): array
    {
        $template = config("communication-hub.recognition_templates.{$templateCode}");

        if (! $template) {
            throw new \InvalidArgumentException('Unknown recognition template.');
        }

        $replacements = [
            '{{honoree_name}}' => $honoree->name,
        ];

        return [
            'title' => strtr($template['title'], $replacements),
            'summary' => strtr($template['summary'], $replacements),
            'body' => strtr($template['body'], $replacements),
            'badge_slug' => $template['badge_slug'] ?? null,
            'recognition_type' => $templateCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRecognitionPost(array $data, User $author): MessageCenterAnnouncement
    {
        $metadata = array_filter([
            'recognition_type' => $data['recognition_type'] ?? null,
            'honoree_user_id' => $data['honoree_user_id'] ?? null,
            'badge_id' => $data['badge_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $announcement = $this->hub->createDraft([
            'category_code' => 'recognition',
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'body' => $data['body'],
            'priority' => $data['priority'] ?? 'informational',
            'audience_type' => $data['audience_type'] ?? 'all',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'metadata' => $metadata,
        ], $author);

        return $announcement->fresh(['category', 'creator']);
    }

    public function publishRecognition(MessageCenterAnnouncement $announcement, User $publisher): MessageCenterAnnouncement
    {
        $published = $this->hub->publish($announcement);
        $this->awardBadgeFromAnnouncement($published, $publisher);

        return $published->fresh(['category', 'creator']);
    }

    public function awardBadgeFromAnnouncement(MessageCenterAnnouncement $announcement, User $awardedBy): ?UserBadge
    {
        $metadata = $announcement->metadata ?? [];
        $honoreeId = (int) ($metadata['honoree_user_id'] ?? 0);
        $badgeId = (int) ($metadata['badge_id'] ?? 0);

        if ($honoreeId <= 0 || $badgeId <= 0) {
            return null;
        }

        return UserBadge::query()->firstOrCreate(
            [
                'user_id' => $honoreeId,
                'badge_id' => $badgeId,
                'announcement_id' => $announcement->id,
            ],
            [
                'awarded_by' => $awardedBy->id,
                'awarded_at' => now(),
            ],
        );
    }

    /**
     * @return Collection<int, UserBadge>
     */
    public function recentAwards(int $limit = 12): Collection
    {
        return UserBadge::query()
            ->with(['user', 'badge', 'announcement'])
            ->orderByDesc('awarded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{honoree: array{id: int, name: string}|null, badge: array{id: int, name: string, icon: string|null, color: string|null}|null, recognition_type: string|null}
     */
    public function recognitionContext(MessageCenterAnnouncement $announcement): array
    {
        $metadata = $announcement->metadata ?? [];
        $honoree = null;
        $badge = null;

        if (! empty($metadata['honoree_user_id'])) {
            $user = User::query()->find($metadata['honoree_user_id']);
            if ($user) {
                $honoree = ['id' => $user->id, 'name' => $user->name];
            }
        }

        if (! empty($metadata['badge_id'])) {
            $badgeModel = Badge::query()->find($metadata['badge_id']);
            if ($badgeModel) {
                $badge = [
                    'id' => $badgeModel->id,
                    'name' => $badgeModel->name,
                    'icon' => $badgeModel->icon,
                    'color' => $badgeModel->color,
                ];
            }
        }

        return [
            'honoree' => $honoree,
            'badge' => $badge,
            'recognition_type' => $metadata['recognition_type'] ?? null,
        ];
    }

    /**
     * @return Collection<int, Badge>
     */
    public function activeBadges(): Collection
    {
        return Badge::query()
            ->where('category', 'recognition')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
