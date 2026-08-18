<?php

namespace App\Http\Controllers;

use App\Models\PortalResource;
use App\Services\ResourceVideoService;
use App\Support\VideoEmbed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceVideosController extends Controller
{
    public function __construct(
        private readonly ResourceVideoService $videos,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->videos->libraryPayload(
            search: $request->string('search')->toString() ?: null,
            category: $request->string('category')->toString() ?: null,
        );

        $favoriteResourceIds = $request->user()
            ->favoritePortalResources()
            ->where('type', 'video')
            ->where('is_published', true)
            ->pluck('resources.id')
            ->all();

        return view('resources.videos', [
            'user' => $request->user(),
            'library' => $payload,
            'favoriteResourceIds' => $favoriteResourceIds,
            'previewVideoId' => $request->integer('video') ?: null,
            'canManageResources' => $request->user()->can('manage resources'),
        ]);
    }

    public function preview(PortalResource $portalResource): JsonResponse
    {
        abort_unless($portalResource->isVideoLibraryItem(), 404);

        $embed = VideoEmbed::parse($portalResource->resolvedVideoSource());

        return response()->json([
            'id' => $portalResource->id,
            'title' => $portalResource->title,
            'description' => $portalResource->description,
            'category' => $portalResource->category,
            'provider' => $embed['provider'],
            'embed_url' => $embed['embed_url'],
            'thumbnail_url' => $embed['thumbnail_url'] ?? $portalResource->videoThumbnailUrl(),
            'external_url' => $portalResource->resolvedAccessUrl(),
        ]);
    }

    public function toggleFavorite(Request $request, PortalResource $portalResource): RedirectResponse
    {
        abort_unless($portalResource->isVideoLibraryItem(), 404);

        $attached = $request->user()->favoritePortalResources()->toggle($portalResource->id);

        $status = $attached['attached'] === [] ? 'favorite-removed' : 'favorite-added';

        return redirect()
            ->route('resources.videos', array_filter([
                'search' => $request->string('search')->toString() ?: null,
                'category' => $request->string('category')->toString() ?: null,
                'video' => $request->integer('video') ?: null,
            ]))
            ->with('status', $status);
    }
}
