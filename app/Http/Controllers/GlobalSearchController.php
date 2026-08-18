<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $search,
    ) {}

    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $type = $request->string('type')->trim()->toString() ?: null;

        $payload = $this->search->search($request->user(), $query);

        if ($type !== null) {
            $payload['sections'] = collect($payload['sections'])
                ->filter(fn (array $section): bool => $section['key'] === $type)
                ->values()
                ->all();
            $payload['total'] = collect($payload['sections'])->sum('count');
        }

        return view('search.index', [
            'query' => $query,
            'results' => $payload,
            'activeType' => $type,
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();

        return response()->json([
            'query' => $query,
            'results' => $this->search->suggest($request->user(), $query),
        ]);
    }
}
