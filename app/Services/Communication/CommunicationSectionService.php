<?php

namespace App\Services\Communication;

use App\Models\AnnouncementCategory;
use App\Models\Badge;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommunicationSectionService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function feedByCategory(User $user, string $categoryCode, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $categoryId = AnnouncementCategory::query()->where('code', $categoryCode)->value('id');
        $page = Paginator::resolveCurrentPage();

        $items = MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->with(['category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->values();

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @return Collection<int, MessageCenterAnnouncement>
     */
    public function featuredByCategory(User $user, string $categoryCode, int $limit = 1): Collection
    {
        $categoryId = AnnouncementCategory::query()->where('code', $categoryCode)->value('id');

        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->where('is_featured', true)
            ->with(['category', 'creator'])
            ->orderBy('featured_sort')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->take($limit)
            ->values();
    }
}
