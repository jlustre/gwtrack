<?php

namespace App\Services\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LeadershipDeskService
{
    public function __construct(
        private readonly CommunicationSectionService $sections,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function feedFor(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->sections->feedByCategory(
            $user,
            config('communication-hub.leadership_desk.category_code', 'leadership'),
            $filters,
            $perPage,
        );
    }

    /**
     * @return Collection<int, MessageCenterAnnouncement>
     */
    public function featuredMessage(User $user): Collection
    {
        return $this->sections->featuredByCategory(
            $user,
            config('communication-hub.leadership_desk.category_code', 'leadership'),
            1,
        );
    }

    /**
     * @return list<array{title: string, slug: string, summary: string|null, published_at: string|null, author: string|null}>
     */
    public function latestForDashboard(User $user, int $limit = 2): array
    {
        return $this->sections->feedByCategory($user, 'leadership', [], 20)
            ->getCollection()
            ->take($limit)
            ->map(fn (MessageCenterAnnouncement $announcement) => [
                'title' => $announcement->title,
                'slug' => $announcement->slug,
                'summary' => $announcement->summary,
                'published_at' => $announcement->published_at?->diffForHumans(),
                'author' => $announcement->creator?->name,
            ])
            ->values()
            ->all();
    }
}
