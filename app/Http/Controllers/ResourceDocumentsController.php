<?php

namespace App\Http\Controllers;

use App\Models\PortalResource;
use App\Services\DocumentationPdfService;
use App\Services\ResourceDocumentSeederExporter;
use App\Services\ResourceDocumentService;
use App\Services\ResourcePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceDocumentsController extends Controller
{
    public function __construct(
        private readonly ResourceDocumentService $documents,
        private readonly ResourceDocumentSeederExporter $documentSeederExporter,
        private readonly ResourcePdfService $resourcePdf,
        private readonly DocumentationPdfService $documentationPdf,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->documents->libraryPayload(
            search: $request->string('search')->toString() ?: null,
            category: $request->string('category')->toString() ?: null,
        );

        $favoriteResourceIds = $request->user()
            ->favoritePortalResources()
            ->where('type', 'document')
            ->where('is_published', true)
            ->pluck('resources.id')
            ->all();

        $favoriteRecords = $request->user()
            ->favoritePortalResources()
            ->where('type', 'document')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('resources.documents', [
            'user' => $request->user(),
            'library' => $payload,
            'favoriteResourceIds' => $favoriteResourceIds,
            'favoriteRecords' => $favoriteRecords,
            'canManageDocuments' => $request->user()->canManageDocuments(),
            'canUpdateDocumentSeeder' => $request->user()->canUpdateDocumentSeeder(),
            'previewDocumentId' => $request->integer('document') ?: null,
        ]);
    }

    public function toggleFavorite(Request $request, PortalResource $portalResource): RedirectResponse
    {
        abort_unless($portalResource->is_published && $portalResource->isDocumentLibraryItem(), 404);

        $attached = $request->user()->favoritePortalResources()->toggle($portalResource->id);

        $status = $attached['attached'] === [] ? 'favorite-removed' : 'favorite-added';

        return redirect()
            ->route('resources.documents', array_filter([
                'search' => $request->string('search')->toString() ?: null,
                'category' => $request->string('category')->toString() ?: null,
            ]))
            ->with('status', $status);
    }

    public function updateSeeder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canUpdateDocumentSeeder(), 403);

        $result = $this->documentSeederExporter->export();

        return redirect()
            ->route('resources.documents')
            ->with('status', 'document-seeder-updated')
            ->with('seeder_count', $result['count']);
    }

    public function preview(PortalResource $portalResource): JsonResponse
    {
        $this->ensurePublishedDocument($portalResource);
        abort_unless($portalResource->canPreview(), 404);

        $hasRtf = $portalResource->hasHtmlPreview();
        $hasPdf = $portalResource->hasPdfPreview();

        return response()->json([
            'id' => $portalResource->id,
            'title' => $portalResource->title,
            'description' => $portalResource->description,
            'format' => $portalResource->resolvedFormat(),
            'has_rtf' => $hasRtf,
            'has_pdf' => $hasPdf,
            'default_view' => $hasRtf ? 'rtf' : ($hasPdf ? 'pdf' : 'summary'),
            'rtf_html' => $hasRtf ? $portalResource->content : null,
            'pdf_url' => $hasPdf ? $portalResource->inlinePreviewUrl() : null,
            'external_url' => filled($portalResource->url) ? $portalResource->resolvedAccessUrl() : null,
            'download_url' => $portalResource->resolvedAccessUrl(),
        ]);
    }

    public function view(PortalResource $portalResource): BinaryFileResponse
    {
        $this->ensurePublishedDocument($portalResource);
        abort_unless($portalResource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($portalResource->file_path), 404);

        $filename = basename($portalResource->file_path) ?: str($portalResource->title)->slug().'.pdf';

        return response()->file(
            $disk->path($portalResource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    public function download(PortalResource $portalResource): StreamedResponse
    {
        $this->ensurePublishedDocument($portalResource);
        abort_unless($portalResource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($portalResource->file_path), 404);

        $filename = basename($portalResource->file_path) ?: str($portalResource->title)->slug().'.'.$portalResource->resolvedFormat();

        return $disk->download($portalResource->file_path, $filename);
    }

    public function welcomePacketPdf(): BinaryFileResponse
    {
        $resource = $this->resourcePdf->ensureAssociateWelcomePacketPdf();

        abort_unless($resource && $resource->is_published && $resource->isDocumentLibraryItem(), 404);
        abort_unless($resource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($resource->file_path), 404);

        return response()->file(
            $disk->path($resource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="welcome-packet.pdf"',
            ],
        );
    }

    public function fapOverviewPdf(): BinaryFileResponse
    {
        $resource = $this->resourcePdf->ensureFapOverviewPdf();

        abort_unless($resource && $resource->is_published && $resource->isDocumentLibraryItem(), 404);
        abort_unless($resource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($resource->file_path), 404);

        return response()->file(
            $disk->path($resource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="fap-overview.pdf"',
            ],
        );
    }

    public function codeOfConductPdf(): BinaryFileResponse
    {
        $resource = $this->resourcePdf->ensureCodeOfConductPdf();

        abort_unless($resource && $resource->is_published && $resource->isDocumentLibraryItem(), 404);
        abort_unless($resource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($resource->file_path), 404);

        return response()->file(
            $disk->path($resource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="code-of-conduct.pdf"',
            ],
        );
    }

    public function welcomeOrientationPdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('welcome-orientation');
    }

    public function applicationGuidePdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('application');
    }

    public function goalsGuidePdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('goals-and-performance');
    }

    public function prospectSalesFunnelGuidePdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('prospect-sales-funnel');
    }

    public function fnaManagementGuidePdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('fna-management');
    }

    public function trainingAcademyGuidePdf(): BinaryFileResponse
    {
        return $this->documentationGuidePdf('training-academy');
    }

    public function documentationGuidePdf(string $slug): BinaryFileResponse
    {
        $entry = $this->documentationPdf->entry($slug);
        $title = $this->documentationPdf->titleFor($entry);
        $stableUrl = 'resources/'.str($slug)->slug().'.pdf';

        $resource = $this->documentationPdf->ensurePortalResource($slug, $title, $stableUrl);

        abort_unless($resource && $resource->is_published && $resource->isDocumentLibraryItem(), 404);
        abort_unless($resource->hasDownloadableFile(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($resource->file_path), 404);

        return response()->file(
            $disk->path($resource->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.str($slug)->slug().'.pdf"',
            ],
        );
    }

    private function ensurePublishedDocument(PortalResource $portalResource): void
    {
        abort_unless($portalResource->is_published && $portalResource->isDocumentLibraryItem(), 404);
    }
}
