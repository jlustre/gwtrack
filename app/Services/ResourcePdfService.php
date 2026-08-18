<?php

namespace App\Services;

use App\Models\PortalResource;
use App\Support\DompdfHtml;
use App\Support\ResourceDocumentContents;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResourcePdfService
{
    public function canGenerate(PortalResource $resource): bool
    {
        return in_array($resource->type, ['document', 'file'], true)
            && filled(strip_tags((string) $resource->content));
    }

    public function generate(PortalResource $resource): PortalResource
    {
        if (! $this->canGenerate($resource)) {
            throw ValidationException::withMessages([
                'content' => 'Add document content before generating a PDF.',
            ]);
        }

        $path = $this->storagePathFor($resource);

        if ($resource->file_path && $resource->file_path !== $path) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $pdf = Pdf::loadView('pdf.resource-document', [
            'title' => $resource->title,
            'description' => $resource->description,
            'content' => DompdfHtml::prepare((string) $resource->content),
        ])
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', true);

        Storage::disk('public')->put($path, $pdf->output());

        $resource->update([
            'file_path' => $path,
            'url' => 'storage/'.$path,
            'file_format' => 'PDF',
            'pdf_generated_at' => now(),
        ]);

        return $resource->fresh();
    }

    public function generateIfEligible(int $resourceId): ?PortalResource
    {
        $resource = PortalResource::query()->findOrFail($resourceId);

        if (! $this->canGenerate($resource)) {
            return null;
        }

        return $this->generate($resource);
    }

    /**
     * Generate (or reuse) the Associate Welcome Packet PDF and keep the stable public URL.
     */
    public function ensureAssociateWelcomePacketPdf(?string $stableUrl = 'resources/welcome-packet.pdf'): ?PortalResource
    {
        return $this->ensureNamedDocumentPdf('Associate Welcome Packet', $stableUrl);
    }

    public function ensureFapOverviewPdf(?string $stableUrl = 'resources/fap-overview.pdf'): ?PortalResource
    {
        return $this->ensureNamedDocumentPdf(
            'Field Apprenticeship Program Overview',
            $stableUrl,
            ResourceDocumentContents::fieldApprenticeshipOverview(),
            forceRegenerate: true,
        );
    }

    public function ensureCodeOfConductPdf(?string $stableUrl = 'resources/code-of-conduct.pdf'): ?PortalResource
    {
        return $this->ensureNamedDocumentPdf(
            'Code of Conduct Summary',
            $stableUrl,
            ResourceDocumentContents::codeOfConductSummary(),
            forceRegenerate: true,
        );
    }

    public function ensureNamedDocumentPdf(
        string $title,
        string $stableUrl,
        ?string $content = null,
        bool $forceRegenerate = false,
    ): ?PortalResource {
        $resource = PortalResource::query()
            ->where('title', $title)
            ->whereIn('type', ['document', 'file'])
            ->whereNull('deleted_at')
            ->first();

        if (! $resource) {
            return null;
        }

        if (filled($content)) {
            $resource->update(['content' => $content]);
            $resource->refresh();
        }

        $disk = Storage::disk('public');
        $hasFile = $resource->hasDownloadableFile() && $disk->exists((string) $resource->file_path);
        $isPlaceholderUrl = str_contains((string) $resource->url, 'example.com');

        if ($hasFile && ! $forceRegenerate && ! $isPlaceholderUrl) {
            if ($resource->url !== $stableUrl) {
                $resource->update(['url' => $stableUrl]);
            }

            return $resource->fresh();
        }

        if (! $this->canGenerate($resource)) {
            return null;
        }

        $resource = $this->generate($resource);
        $resource->update(['url' => $stableUrl]);

        return $resource->fresh();
    }

    public function storeUpload(PortalResource $resource, UploadedFile $file): PortalResource
    {
        $path = $this->storagePathFor($resource);

        if ($resource->file_path && $resource->file_path !== $path) {
            Storage::disk('public')->delete($resource->file_path);
        }

        Storage::disk('public')->makeDirectory(dirname($path));

        $file->storeAs(dirname($path), basename($path), 'public');

        $resource->update([
            'file_path' => $path,
            'url' => 'storage/'.$path,
            'file_format' => 'PDF',
            'pdf_generated_at' => now(),
        ]);

        return $resource->fresh();
    }

    private function storagePathFor(PortalResource $resource): string
    {
        return 'resources/documents/'.Str::slug($resource->title).'-'.$resource->id.'.pdf';
    }
}
