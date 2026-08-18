<?php

namespace App\Services\Communication;

use App\Models\AnnouncementBookmark;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementReaction;
use App\Models\MessageCenterAnnouncement;
use App\Models\MessageCenterAnnouncementRead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class AnnouncementEngagementService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
    ) {}

    public function readIdsFor(User $user): Collection
    {
        return MessageCenterAnnouncementRead::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id')
            ->map(fn ($id) => (int) $id);
    }

    public function bookmarkIdsFor(User $user): Collection
    {
        return AnnouncementBookmark::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id');
    }

    public function unreadCountFor(User $user): int
    {
        $readIds = $this->readIdsFor($user);

        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->when($readIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $readIds))
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->count();
    }

    /**
     * @return Collection<int, MessageCenterAnnouncement>
     */
    public function featuredFor(User $user, int $limit = 3): Collection
    {
        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->where('is_featured', true)
            ->with(['category', 'creator'])
            ->orderBy('featured_sort')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, MessageCenterAnnouncement>
     */
    public function pinnedFor(User $user): Collection
    {
        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->where('is_pinned', true)
            ->with(['category', 'creator'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function bookmarksFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $bookmarkIds = AnnouncementBookmark::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->pluck('announcement_id');

        $items = MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->whereIn('id', $bookmarkIds)
            ->with(['category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->sortByDesc(fn (MessageCenterAnnouncement $announcement) => $bookmarkIds->search($announcement->id))
            ->values();

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function isBookmarked(User $user, MessageCenterAnnouncement $announcement): bool
    {
        return AnnouncementBookmark::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function toggleBookmark(User $user, MessageCenterAnnouncement $announcement): bool
    {
        $bookmark = AnnouncementBookmark::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();

            return false;
        }

        AnnouncementBookmark::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * @return array<string, int>
     */
    public function reactionCountsFor(MessageCenterAnnouncement $announcement): array
    {
        return AnnouncementReaction::query()
            ->where('announcement_id', $announcement->id)
            ->selectRaw('reaction, COUNT(*) as total')
            ->groupBy('reaction')
            ->pluck('total', 'reaction')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function userReaction(User $user, MessageCenterAnnouncement $announcement): ?string
    {
        return AnnouncementReaction::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->value('reaction');
    }

    public function toggleReaction(User $user, MessageCenterAnnouncement $announcement, string $reaction): ?string
    {
        $allowed = array_keys(config('communication-hub.reactions', []));

        if (! in_array($reaction, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid reaction type.');
        }

        $existing = AnnouncementReaction::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->reaction === $reaction) {
            $existing->delete();

            return null;
        }

        AnnouncementReaction::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            ['reaction' => $reaction],
        );

        return $reaction;
    }

    public function commentCountFor(MessageCenterAnnouncement $announcement): int
    {
        return AnnouncementComment::query()
            ->where('announcement_id', $announcement->id)
            ->count();
    }

    /**
     * @return Collection<int, AnnouncementComment>
     */
    public function commentsFor(MessageCenterAnnouncement $announcement): Collection
    {
        return AnnouncementComment::query()
            ->where('announcement_id', $announcement->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at')
            ->get();
    }

    public function addComment(
        User $user,
        MessageCenterAnnouncement $announcement,
        string $body,
        ?int $parentId = null,
    ): AnnouncementComment {
        if ($parentId) {
            $parent = AnnouncementComment::query()
                ->where('announcement_id', $announcement->id)
                ->whereKey($parentId)
                ->firstOrFail();

            if ($parent->parent_id !== null) {
                throw new \InvalidArgumentException('Replies are limited to one level deep.');
            }
        }

        return AnnouncementComment::query()->create([
            'announcement_id' => $announcement->id,
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'body' => trim($body),
        ]);
    }

    public function deleteComment(User $user, AnnouncementComment $comment): void
    {
        if ($comment->user_id !== $user->id && ! $user->can('delete announcements')) {
            throw new \InvalidArgumentException('You cannot delete this comment.');
        }

        $comment->delete();
    }

    /**
     * @param  list<int>  $announcementIds
     * @return array<int, array{reactions: int, comments: int}>
     */
    public function engagementSummariesFor(array $announcementIds): array
    {
        if ($announcementIds === []) {
            return [];
        }

        $reactions = AnnouncementReaction::query()
            ->whereIn('announcement_id', $announcementIds)
            ->selectRaw('announcement_id, COUNT(*) as total')
            ->groupBy('announcement_id')
            ->pluck('total', 'announcement_id');

        $comments = AnnouncementComment::query()
            ->whereIn('announcement_id', $announcementIds)
            ->selectRaw('announcement_id, COUNT(*) as total')
            ->groupBy('announcement_id')
            ->pluck('total', 'announcement_id');

        $summaries = [];

        foreach ($announcementIds as $id) {
            $summaries[$id] = [
                'reactions' => (int) ($reactions[$id] ?? 0),
                'comments' => (int) ($comments[$id] ?? 0),
            ];
        }

        return $summaries;
    }
}
