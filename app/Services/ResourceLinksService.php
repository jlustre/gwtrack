<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Support\ResourceLinkCategories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResourceLinksService
{
    /**
     * @return array{
     *     featured: Collection<int, PortalResource>,
     *     links: LengthAwarePaginator,
     *     stats: array<string, int>,
     *     categoryCounts: array<string, int>,
     *     categories: array<string, array{label: string, description: string, accent: string, icon: string}>,
     *     filters: array{search: ?string, category: ?string}
     * }
     */
    public function libraryPayload(?string $search = null, ?string $category = null, int $perPage = 12): array
    {
        $categories = ResourceLinkCategories::all();
        $baseQuery = $this->publishedLinksQuery();

        $categoryCounts = (clone $baseQuery)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();

        $featured = (clone $baseQuery)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(6)
            ->get();

        $linksQuery = clone $baseQuery;

        if (filled($search)) {
            $linksQuery->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('url', 'like', '%'.$search.'%');
            });
        }

        if (filled($category) && ResourceLinkCategories::isValid($category)) {
            $linksQuery->where('category', $category);
        }

        $links = $linksQuery
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        $totalPublished = (int) (clone $baseQuery)->count();

        return [
            'featured' => $featured,
            'links' => $links,
            'stats' => [
                'total' => $totalPublished,
                'featured' => $featured->count(),
                'categories' => count(array_filter($categoryCounts)),
            ],
            'categoryCounts' => $categoryCounts,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
        ];
    }

    /**
     * @return Collection<int, PortalResource>
     */
    public function dashboardLinks(int $limit = 6): Collection
    {
        $featured = (clone $this->publishedLinksQuery())
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($featured->count() >= $limit) {
            return $featured;
        }

        $remaining = $limit - $featured->count();

        $additional = (clone $this->publishedLinksQuery())
            ->when(
                $featured->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $featured->pluck('id')),
            )
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit($remaining)
            ->get();

        return $featured->concat($additional);
    }

    private function publishedLinksQuery()
    {
        return PortalResource::query()
            ->where('type', 'link')
            ->where('is_published', true)
            ->whereNotNull('url')
            ->where('url', '!=', '');
    }
}
