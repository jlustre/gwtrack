<?php

namespace App\Services\Communication;

use App\Models\AnnouncementAcknowledgement;
use App\Models\AnnouncementCategory;
use App\Models\MessageCenterAnnouncement;
use App\Models\MessageCenterAnnouncementRead;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CommunicationHubService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
        private readonly NotificationOrchestrator $notifications,
        private readonly AnnouncementEngagementService $engagement,
        private readonly AnnouncementAcknowledgementService $acknowledgements,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, User $author): MessageCenterAnnouncement
    {
        $category = $this->resolveCategory($data);

        return MessageCenterAnnouncement::query()->create([
            'category_id' => $category?->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'summary' => $data['summary'] ?? Str::limit(strip_tags($data['body']), 200),
            'body' => $data['body'],
            'priority' => $data['priority'] ?? $category?->default_priority ?? 'informational',
            'status' => 'draft',
            'requires_acknowledgement' => (bool) ($data['requires_acknowledgement'] ?? $category?->requires_acknowledgement_default ?? false),
            'audience_type' => $data['audience_type'] ?? 'all',
            'audience_config' => $data['audience_config'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'featured_sort' => (int) ($data['featured_sort'] ?? 0),
            'expires_at' => $data['expires_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'calendar_event_id' => $data['calendar_event_id'] ?? null,
            'created_by' => $author->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAnnouncement(MessageCenterAnnouncement $announcement, array $data): MessageCenterAnnouncement
    {
        if ($announcement->isPublished() && ! ($data['allow_published_edit'] ?? false)) {
            throw new InvalidArgumentException('Published announcements cannot be edited without explicit override.');
        }

        $category = isset($data['category_id']) || isset($data['category_code'])
            ? $this->resolveCategory($data)
            : $announcement->category;

        $announcement->fill([
            'category_id' => $category?->id ?? $announcement->category_id,
            'title' => $data['title'] ?? $announcement->title,
            'summary' => $data['summary'] ?? $announcement->summary,
            'body' => $data['body'] ?? $announcement->body,
            'priority' => $data['priority'] ?? $announcement->priority,
            'requires_acknowledgement' => $data['requires_acknowledgement'] ?? $announcement->requires_acknowledgement,
            'audience_type' => $data['audience_type'] ?? $announcement->audience_type,
            'audience_config' => $data['audience_config'] ?? $announcement->audience_config,
            'is_pinned' => $data['is_pinned'] ?? $announcement->is_pinned,
            'is_featured' => $data['is_featured'] ?? $announcement->is_featured,
            'featured_sort' => $data['featured_sort'] ?? $announcement->featured_sort,
            'expires_at' => $data['expires_at'] ?? $announcement->expires_at,
        ]);

        if (isset($data['title']) && $data['title'] !== $announcement->getOriginal('title')) {
            $announcement->slug = $this->uniqueSlug($data['title'], $announcement->id);
        }

        $announcement->save();

        return $announcement->fresh(['category', 'creator']);
    }

    public function publish(MessageCenterAnnouncement $announcement): MessageCenterAnnouncement
    {
        $announcement->forceFill([
            'status' => 'published',
            'published_at' => $announcement->published_at ?? now(),
        ])->save();

        $recipientIds = $this->audience->resolveUserIds($announcement);

        if ($recipientIds !== []) {
            $this->notifications->dispatch('announcement_published', [
                'recipients' => ['user_ids' => $recipientIds],
                'template_data' => [
                    'announcement_title' => $announcement->title,
                    'user_name' => config('app.name'),
                ],
                'priority' => $this->notificationPriority($announcement->priority),
                'module' => 'announcement',
                'related' => [
                    'type' => MessageCenterAnnouncement::class,
                    'id' => $announcement->id,
                ],
                'action_link' => [
                    'route' => 'communications.show',
                    'params' => ['announcement' => $announcement->slug],
                ],
                'queue' => config('notifications.queue', true),
            ]);
        }

        return $announcement->fresh(['category', 'creator']);
    }

    public function archive(MessageCenterAnnouncement $announcement): MessageCenterAnnouncement
    {
        $announcement->forceFill(['status' => 'archived'])->save();

        return $announcement->fresh(['category', 'creator']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function feedFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $readIds = $this->engagement->readIdsFor($user);

        $items = MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->with(['category', 'creator'])
            ->when(filled($filters['category_id'] ?? null), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(filled($filters['priority'] ?? null), fn ($query) => $query->where('priority', $filters['priority']))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->when(filled($filters['author_id'] ?? null), fn ($query) => $query->where('created_by', $filters['author_id']))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->values();

        if (($filters['unread_only'] ?? false) === true) {
            $items = $items->reject(fn (MessageCenterAnnouncement $announcement) => $readIds->contains($announcement->id))->values();
        }

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function markRead(User $user, MessageCenterAnnouncement $announcement, bool $openedFull = false): void
    {
        $existing = MessageCenterAnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->first();

        $payload = ['read_at' => now()];

        if (! $existing?->first_viewed_at) {
            $payload['first_viewed_at'] = now();
        }

        if ($openedFull) {
            $payload['opened_full'] = true;
        }

        MessageCenterAnnouncementRead::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            $payload,
        );

        if (! $existing) {
            $announcement->increment('view_count');
        }
    }

    public function acknowledge(User $user, MessageCenterAnnouncement $announcement): void
    {
        AnnouncementAcknowledgement::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            ['acknowledged_at' => now()],
        );

        $this->markRead($user, $announcement);
    }

    public function hasAcknowledged(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return AnnouncementAcknowledgement::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function hasRead(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return MessageCenterAnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * @return list<array{title: string, meta: string, slug: string, is_unread: bool, priority: string}>
     */
    public function latestForDashboard(User $user, int $limit = 3): array
    {
        $readIds = $this->engagement->readIdsFor($user);

        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->orderByDesc('published_at')
            ->limit(20)
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->take($limit)
            ->map(fn (MessageCenterAnnouncement $announcement) => [
                'title' => $announcement->title,
                'meta' => 'Posted '.$announcement->published_at?->diffForHumans(),
                'slug' => $announcement->slug,
                'is_unread' => ! $readIds->contains($announcement->id),
                'priority' => $announcement->priority,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     unread_count: int,
     *     featured: list<array{title: string, summary: string|null, slug: string, category: string|null, priority: string}>,
     *     announcements: list<array{title: string, meta: string, slug: string, is_unread: bool, priority: string}>,
     *     pending_critical: list<array{title: string, slug: string, priority: string, summary: string|null}>
     * }
     */
    public function dashboardCommunicationsFor(User $user): array
    {
        return [
            'unread_count' => $this->engagement->unreadCountFor($user),
            'featured' => $this->engagement->featuredFor($user, 2)
                ->map(fn (MessageCenterAnnouncement $announcement) => [
                    'title' => $announcement->title,
                    'summary' => $announcement->summary,
                    'slug' => $announcement->slug,
                    'category' => $announcement->category?->name,
                    'priority' => $announcement->priority,
                ])
                ->values()
                ->all(),
            'announcements' => $this->latestForDashboard($user),
            'pending_critical' => $this->acknowledgements->pendingCriticalFor($user)
                ->map(fn (MessageCenterAnnouncement $announcement) => [
                    'title' => $announcement->title,
                    'slug' => $announcement->slug,
                    'priority' => $announcement->priority,
                    'summary' => $announcement->summary,
                ])
                ->values()
                ->all(),
        ];
    }

    public function toggleBookmark(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return $this->engagement->toggleBookmark($user, $announcement);
    }

    public function isBookmarked(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return $this->engagement->isBookmarked($user, $announcement);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCategory(array $data): ?AnnouncementCategory
    {
        if (! empty($data['category_id'])) {
            return AnnouncementCategory::query()->find($data['category_id']);
        }

        if (! empty($data['category_code'])) {
            return AnnouncementCategory::query()->where('code', $data['category_code'])->first();
        }

        return null;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'announcement';
        $slug = $base;
        $counter = 1;

        while (
            MessageCenterAnnouncement::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function notificationPriority(string $priority): string
    {
        return config("communication-hub.priorities.{$priority}.notification", 'info');
    }
}
