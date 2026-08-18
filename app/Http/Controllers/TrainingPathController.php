<?php

namespace App\Http\Controllers;

use App\Models\TrainingPath;
use App\Services\Training\TrainingPathService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingPathController extends Controller
{
    public function __construct(private readonly TrainingPathService $paths) {}

    public function index(Request $request): View
    {
        return view('training.paths.index', [
            'rows' => $this->paths->pathRowsFor($request->user()),
        ]);
    }

    public function show(Request $request, TrainingPath $path): View
    {
        abort_unless($path->is_active, 404);

        return view('training.paths.show', [
            'path' => $path,
        ]);
    }
}
