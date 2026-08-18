<?php

namespace App\Http\Controllers;

use App\Services\ResourceHubService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceHubController extends Controller
{
    public function __construct(
        private readonly ResourceHubService $hub,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $dashboard = $this->hub->dashboardFor($user);

        return view('resources.index', [
            'user' => $user,
            'stats' => $dashboard['stats'],
            'favorites' => $dashboard['favorites'],
            'featuredDocuments' => $dashboard['featuredDocuments'],
            'featuredLinks' => $dashboard['featuredLinks'],
            'recentDocuments' => $dashboard['recentDocuments'],
            'documentCategories' => $dashboard['documentCategories'],
            'linkCategories' => $dashboard['linkCategories'],
            'librarySections' => $dashboard['librarySections'],
            'documentCategoryDefinitions' => \App\Support\ResourceDocumentCategories::all(),
            'canManageDocuments' => $user->canManageDocuments(),
            'canManageResources' => $user->can('manage resources'),
        ]);
    }
}
