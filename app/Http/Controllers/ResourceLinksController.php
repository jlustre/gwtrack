<?php

namespace App\Http\Controllers;

use App\Services\ResourceLinksService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceLinksController extends Controller
{
    public function __construct(
        private readonly ResourceLinksService $links,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->links->libraryPayload(
            search: $request->string('search')->toString() ?: null,
            category: $request->string('category')->toString() ?: null,
        );

        return view('resources.links', [
            'user' => $request->user(),
            'library' => $payload,
            'canManageResources' => $request->user()->can('manage resources'),
        ]);
    }
}
