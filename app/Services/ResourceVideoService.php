<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Support\ResourceVideoCategories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResourceVideoService
{
    /**
     * @return array{
     *     featured: Collection<int, PortalResource>,
     *     videos: LengthAwarePaginator,
     *     stats: array<string, int>,
     *     categoryCounts: array<string, int>,
     *     categories: array<string, array{label: string, description: string, accent: string}>,
     *     filters: array{search: ?string, category: ?string}
     * }
     */
    public function libraryPayload(?string $search = null, ?string $category = null, int $perPage = 12): array
    {
        $categories = ResourceVideoCategories::all();
        $baseQuery = $this->publishedVideosQuery();

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

        $videosQuery = clone $baseQuery;

        if (filled($search)) {
            $videosQuery->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if (filled($category) && ResourceVideoCategories::isValid($category)) {
            $videosQuery->where('category', $category);
        }

        $videos = $videosQuery
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        $totalPublished = (int) (clone $baseQuery)->count();

        return [
            'featured' => $featured,
            'videos' => $videos,
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

    private function publishedVideosQuery()
    {
        return PortalResource::query()
            ->where('type', 'video')
            ->where('is_published', true)
            ->where(function ($query): void {
                $query->whereNotNull('url')->where('url', '!=', '')
                    ->orWhereNotNull('file_path')->where('file_path', '!=', '');
            });
    }
}
