<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Models\User;
use App\Support\DocumentLinkExtractor;
use App\Support\ResourceLinkCategories;
use Illuminate\Support\Collection;

class DocumentLinkSyncService
{
    /**
     * @return array{created: int, updated: int, total: int}
     */
    public function syncAll(): array
    {
        $created = 0;
        $updated = 0;

        $documents = PortalResource::query()
            ->whereIn('type', ['document', 'file'])
            ->where('is_published', true)
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $aggregated = $this->aggregateLinksFromDocuments($documents);
        $creatorId = User::query()->value('id');

        foreach ($aggregated as $index => $link) {
            $existing = PortalResource::query()
                ->where('type', 'link')
                ->where('url', $link['url'])
                ->first();

            $attributes = [
                'created_by' => $creatorId,
                'title' => $link['title'],
                'description' => $this->buildDescription($link['sources']),
                'category' => $this->inferCategory($link['url'], $link['title'], $link['document_category']),
                'sort_order' => 100 + $index,
                'url' => $link['url'],
                'file_format' => 'LINK',
                'is_published' => true,
            ];

            if ($existing) {
                $existing->fill(array_merge($attributes, [
                    'is_featured' => $existing->is_featured,
                ]));
                $existing->save();
                $updated++;
            } else {
                PortalResource::query()->create(array_merge($attributes, [
                    'is_featured' => $this->shouldFeature($link['url'], $link['title']),
                ]));
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $aggregated->count(),
        ];
    }

    /**
     * @param  Collection<int, PortalResource>  $documents
     * @return Collection<int, array{url: string, title: string, document_category: ?string, sources: array<int, string>}>
     */
    private function aggregateLinksFromDocuments(Collection $documents): Collection
    {
        /** @var array<string, array{url: string, title: string, document_category: ?string, sources: array<int, string>}> $byUrl */
        $byUrl = [];

        foreach ($documents as $document) {
            foreach (DocumentLinkExtractor::fromHtml((string) $document->content) as $extracted) {
                $url = $extracted['url'];

                if (! isset($byUrl[$url])) {
                    $byUrl[$url] = [
                        'url' => $url,
                        'title' => $extracted['title'],
                        'document_category' => $document->category,
                        'sources' => [],
                    ];
                }

                if (! in_array($document->title, $byUrl[$url]['sources'], true)) {
                    $byUrl[$url]['sources'][] = $document->title;
                }

                if (strlen($extracted['title']) > strlen($byUrl[$url]['title'])) {
                    $byUrl[$url]['title'] = $extracted['title'];
                }
            }
        }

        return collect(array_values($byUrl));
    }

    /**
     * @param  array<int, string>  $sources
     */
    private function buildDescription(array $sources): string
    {
        if ($sources === []) {
            return 'Synced from a published document.';
        }

        if (count($sources) === 1) {
            return 'Referenced in '.$sources[0].'.';
        }

        return 'Referenced in '.implode(', ', array_slice($sources, 0, -1)).', and '.end($sources).'.';
    }

    private function inferCategory(string $url, string $title, ?string $documentCategory): string
    {
        $haystack = strtolower($url.' '.$title);

        if (str_contains($haystack, 'zoom.us') || str_contains($haystack, 'zoom.com')) {
            if (str_contains($haystack, 'team') || str_contains($haystack, 'huddle') || str_contains($haystack, 'leadership')) {
                return 'team';
            }

            if (str_contains($haystack, 'cfm') || str_contains($haystack, 'mentor') || str_contains($haystack, 'office hour')) {
                return 'mentorship';
            }

            if (str_contains($haystack, 'training') || str_contains($haystack, 'fast start') || str_contains($haystack, 'product') || str_contains($haystack, 'compliance')) {
                return 'training';
            }

            return 'zoom';
        }

        if (str_contains($haystack, 'book') || str_contains($haystack, 'calendly') || str_contains($haystack, 'schedule')) {
            return 'tools';
        }

        return match ($documentCategory) {
            'onboarding', 'guides' => 'training',
            'forms' => 'tools',
            'scripts' => 'general',
            default => 'general',
        };
    }

    private function shouldFeature(string $url, string $title): bool
    {
        $haystack = strtolower($url.' '.$title);

        return str_contains($haystack, 'team huddle')
            || str_contains($haystack, 'fast start')
            || str_contains($haystack, 'office hour');
    }
}
