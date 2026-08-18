<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Support\ResourceDocumentCategories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResourceDocumentService
{
    /**
     * @return array{
     *     featured: Collection<int, PortalResource>,
     *     documents: LengthAwarePaginator,
     *     stats: array<string, int>,
     *     categoryCounts: array<string, int>,
     *     categories: array<string, array{label: string, description: string, accent: string}>,
     *     filters: array{search: ?string, category: ?string}
     * }
     */
    public function libraryPayload(?string $search = null, ?string $category = null, int $perPage = 12): array
    {
        $categories = ResourceDocumentCategories::all();
        $baseQuery = $this->publishedDocumentsQuery();

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

        $documentsQuery = clone $baseQuery;

        if (filled($search)) {
            $documentsQuery->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if (filled($category) && ResourceDocumentCategories::isValid($category)) {
            $documentsQuery->where('category', $category);
        }

        $documents = $documentsQuery
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        $totalPublished = (int) (clone $baseQuery)->count();

        return [
            'featured' => $featured,
            'documents' => $documents,
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

    private function publishedDocumentsQuery()
    {
        return PortalResource::query()
            ->whereIn('type', ['document', 'file'])
            ->where('is_published', true);
    }
}
