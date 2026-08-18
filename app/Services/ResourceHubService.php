<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Models\User;
use App\Support\ResourceDocumentCategories;
use App\Support\ResourceLinkCategories;
use Illuminate\Support\Collection;

class ResourceHubService
{
    public function __construct(
        private readonly ResourceDocumentService $documents,
        private readonly ResourceLinksService $links,
    ) {}

    /**
     * @return array{
     *     stats: array<string, int>,
     *     favorites: Collection<int, PortalResource>,
     *     featuredDocuments: Collection<int, PortalResource>,
     *     featuredLinks: Collection<int, PortalResource>,
     *     recentDocuments: Collection<int, PortalResource>,
     *     documentCategories: array<int, array{key: string, label: string, description: string, accent: string, count: int}>,
     *     linkCategories: array<int, array{key: string, label: string, description: string, accent: string, count: int}>,
     *     librarySections: array<int, array{title: string, description: string, route: string, count: int, theme: string, status: ?string}>
     * }
     */
    public function dashboardFor(User $user): array
    {
        $documentPayload = $this->documents->libraryPayload(perPage: 1);
        $linkPayload = $this->links->libraryPayload(perPage: 1);

        $documentCount = $documentPayload['stats']['total'];
        $linkCount = $linkPayload['stats']['total'];
        $videoCount = $this->publishedByType('video');
        $webinarCount = $this->publishedByType('webinar');

        $favorites = $user->favoritePortalResources()
            ->where('is_published', true)
            ->whereIn('type', ['document', 'file'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(8)
            ->get();

        $featuredDocuments = PortalResource::query()
            ->whereIn('type', ['document', 'file'])
            ->where('is_published', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(4)
            ->get();

        $featuredLinks = PortalResource::query()
            ->where('type', 'link')
            ->where('is_published', true)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(4)
            ->get();

        $recentDocuments = PortalResource::query()
            ->whereIn('type', ['document', 'file'])
            ->where('is_published', true)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $documentCategories = $this->topCategories(
            $documentPayload['categoryCounts'],
            ResourceDocumentCategories::all(),
        );

        $linkCategories = $this->topCategories(
            $linkPayload['categoryCounts'],
            ResourceLinkCategories::all(),
        );

        $featuredTotal = (int) PortalResource::query()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->where(function ($query): void {
                $query->whereIn('type', ['document', 'file'])
                    ->orWhere(function ($linkQuery): void {
                        $linkQuery->where('type', 'link')
                            ->whereNotNull('url')
                            ->where('url', '!=', '');
                    });
            })
            ->count();

        return [
            'stats' => [
                'documents' => $documentCount,
                'links' => $linkCount,
                'favorites' => $favorites->count(),
                'featured' => $featuredTotal,
                'videos' => $videoCount,
                'webinars' => $webinarCount,
            ],
            'favorites' => $favorites,
            'featuredDocuments' => $featuredDocuments,
            'featuredLinks' => $featuredLinks,
            'recentDocuments' => $recentDocuments,
            'documentCategories' => $documentCategories,
            'linkCategories' => $linkCategories,
            'librarySections' => [
                [
                    'title' => 'Documents',
                    'description' => 'Onboarding packets, forms, scripts, guides, and compliance files.',
                    'route' => route('resources.documents'),
                    'count' => $documentCount,
                    'theme' => 'navy',
                    'status' => null,
                ],
                [
                    'title' => 'Links',
                    'description' => 'Zoom rooms, team calls, training sessions, and quick-access URLs.',
                    'route' => route('resources.links'),
                    'count' => $linkCount,
                    'theme' => 'cyan',
                    'status' => null,
                ],
                [
                    'title' => 'Videos',
                    'description' => 'Short training clips, leadership messages, and product education.',
                    'route' => route('resources.videos'),
                    'count' => $videoCount,
                    'theme' => 'violet',
                    'status' => $videoCount === 0 ? 'Coming soon' : null,
                ],
                [
                    'title' => 'Recorded Webinars',
                    'description' => 'Team calls, field trainings, and replay links.',
                    'route' => route('resources.recorded-webinars'),
                    'count' => $webinarCount,
                    'theme' => 'emerald',
                    'status' => $webinarCount === 0 ? 'Coming soon' : null,
                ],
                [
                    'title' => 'Associate Agreement',
                    'description' => 'Complete and sign your associate participation agreement online.',
                    'route' => route('resources.forms.associate-participation-agreement'),
                    'count' => 1,
                    'theme' => 'gold',
                    'status' => 'Interactive form',
                ],
            ],
        ];
    }

    private function publishedByType(string $type): int
    {
        return (int) PortalResource::query()
            ->where('type', $type)
            ->where('is_published', true)
            ->count();
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, array{label: string, description: string, accent: string}>  $definitions
     * @return array<int, array{key: string, label: string, description: string, accent: string, count: int}>
     */
    private function topCategories(array $counts, array $definitions): array
    {
        return collect($counts)
            ->sortDesc()
            ->take(4)
            ->map(function (int $count, string $key) use ($definitions): array {
                $meta = $definitions[$key] ?? $definitions['general'] ?? [
                    'label' => str($key)->headline()->toString(),
                    'description' => '',
                    'accent' => 'bg-slate-100 text-slate-700 border-slate-200',
                ];

                return [
                    'key' => $key,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'accent' => $meta['accent'],
                    'count' => $count,
                ];
            })
            ->values()
            ->all();
    }
}
