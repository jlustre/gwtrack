<?php

namespace App\Services;

use App\Models\PortalResource;
use Illuminate\Support\Facades\File;

class ResourceDocumentSeederExporter
{
    public function seederPath(): string
    {
        return database_path('seeders/ResourceDocumentSeeder.php');
    }

    /**
     * @return array{count: int, path: string}
     */
    public function export(): array
    {
        $documents = PortalResource::query()
            ->whereIn('type', ['document', 'file'])
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $payload = $documents->map(function (PortalResource $document): array {
            $entry = [
                'title' => $document->title,
                'description' => $document->description,
                'category' => $document->category ?: 'general',
                'sort_order' => (int) $document->sort_order,
                'type' => $document->type,
                'is_featured' => (bool) $document->is_featured,
                'is_published' => (bool) $document->is_published,
            ];

            if (filled($document->content)) {
                $entry['content'] = $document->content;
            }

            if (filled($document->url)) {
                $entry['url'] = $document->url;
            }

            if (filled($document->file_format)) {
                $entry['file_format'] = $document->file_format;
            }

            return $entry;
        })->all();

        $path = $this->seederPath();
        File::put($path, $this->buildSeederFile($payload));

        return [
            'count' => count($payload),
            'path' => $path,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function buildSeederFile(array $documents): string
    {
        $exportedDocuments = $this->exportPhpArray($documents, 2);

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\PortalResource;
use App\\Models\\User;
use App\\Services\\DocumentationPdfService;
use App\\Services\\ResourcePdfService;
use Illuminate\\Database\\Seeder;

class ResourceDocumentSeeder extends Seeder
{
    public function run(): void
    {
        \$creatorId = User::query()->value('id');

        \$documents = {$exportedDocuments};

        foreach (\$documents as \$document) {
            PortalResource::query()->updateOrCreate(
                [
                    'title' => \$document['title'],
                    'type' => \$document['type'] ?? 'document',
                ],
                [
                    'created_by' => \$creatorId,
                    'description' => \$document['description'] ?? null,
                    'content' => \$document['content'] ?? null,
                    'category' => \$document['category'] ?? 'general',
                    'sort_order' => \$document['sort_order'] ?? 0,
                    'is_featured' => \$document['is_featured'] ?? false,
                    'url' => \$document['url'] ?? null,
                    'file_format' => \$document['file_format'] ?? null,
                    'is_published' => \$document['is_published'] ?? true,
                ],
            );
        }

        app(ResourcePdfService::class)->ensureAssociateWelcomePacketPdf();
        app(ResourcePdfService::class)->ensureFapOverviewPdf();
        app(ResourcePdfService::class)->ensureCodeOfConductPdf();
        app(DocumentationPdfService::class)->ensurePortalResource(
            'welcome-orientation',
            'GWTracks Welcome Orientation',
            'resources/welcome-orientation.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'application',
            'GWTracks Application User Guide',
            'resources/application.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'goals-and-performance',
            'Goals & Performance User Guide',
            'resources/goals-and-performance.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'prospect-sales-funnel',
            'Prospects & Sales Funnel User Guide',
            'resources/prospect-sales-funnel.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'fna-management',
            'FNA Management User Guide',
            'resources/fna-management.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'training-academy',
            'Training Academy User Guide',
            'resources/training-academy.pdf',
        );
    }
}

PHP;
    }

    private function exportPhpArray(array $value, int $indent = 0): string
    {
        $export = var_export($value, true);
        $export = preg_replace('/^([ ]*)array \\(/m', '$1[', $export);
        $export = preg_replace('/\\)(,?)$/m', ']$1', $export);

        if ($indent === 0) {
            return $export;
        }

        $spaces = str_repeat(' ', $indent * 4);

        return preg_replace('/^/m', $spaces, $export);
    }
}
