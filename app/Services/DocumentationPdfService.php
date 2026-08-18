<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Support\DocumentationMarkdown;
use App\Support\DompdfHtml;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentationPdfService
{
    /**
     * @return array{module: string, title?: string, summary?: string, slug: string, file: string}
     */
    public function entry(string $slug): array
    {
        $entry = collect(config('support-documentation.modules', []))
            ->first(fn (array $module): bool => ($module['slug'] ?? null) === $slug && filled($module['file'] ?? null));

        if (! $entry) {
            throw new NotFoundHttpException();
        }

        $path = base_path('docs/'.$entry['file']);

        if (! File::isFile($path)) {
            throw new NotFoundHttpException();
        }

        $entry['path'] = $path;

        return $entry;
    }

    public function titleFor(array $entry): string
    {
        return $entry['title'] ?? $entry['module'];
    }

    public function htmlFor(string $slug): string
    {
        $entry = $this->entry($slug);

        return DompdfHtml::prepare(
            DocumentationMarkdown::toHtml(File::get($entry['path']))
        );
    }

    /**
     * @return array{title: string, summary: string, content: string, meta: array<string, string>}
     */
    public function viewData(string $slug): array
    {
        $entry = $this->entry($slug);
        $title = $this->titleFor($entry);
        $summary = (string) ($entry['summary'] ?? '');

        return [
            'title' => $title,
            'summary' => $summary,
            'content' => $this->htmlFor($slug),
            'meta' => array_filter([
                'Audience' => 'Associates, CFMs, leaders, and owners',
                'Format' => 'Orientation guide',
                'Portal' => 'GWTrack',
            ]),
        ];
    }

    public function pdf(string $slug)
    {
        $data = $this->viewData($slug);

        return Pdf::loadView('pdf.documentation-guide', $data)
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true);
    }

    public function storagePathFor(string $slug): string
    {
        return 'resources/documents/'.Str::slug($slug).'.pdf';
    }

    public function ensureStored(string $slug): string
    {
        $path = $this->storagePathFor($slug);
        $disk = Storage::disk('public');

        $disk->put($path, $this->pdf($slug)->output());

        return $path;
    }

    public function ensurePortalResource(
        string $slug,
        ?string $resourceTitle = null,
        ?string $stableUrl = null,
    ): ?PortalResource {
        $entry = $this->entry($slug);
        $title = $resourceTitle ?: $this->titleFor($entry);
        $stableUrl ??= 'resources/'.Str::slug($slug).'.pdf';

        $resource = PortalResource::query()
            ->where('title', $title)
            ->whereIn('type', ['document', 'file'])
            ->whereNull('deleted_at')
            ->first();

        if (! $resource) {
            return null;
        }

        $path = $this->ensureStored($slug);

        $resource->update([
            'description' => $entry['summary'] ?? $resource->description,
            'file_path' => $path,
            'url' => $stableUrl,
            'file_format' => 'PDF',
            'pdf_generated_at' => now(),
            'is_published' => true,
            'is_featured' => true,
            'category' => $resource->category ?: 'guides',
        ]);

        return $resource->fresh();
    }
}
