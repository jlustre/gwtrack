<?php

namespace App\Http\Controllers;

use App\Services\Training\TrainingAssignmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingAssignmentController extends Controller
{
    public function __construct(private readonly TrainingAssignmentService $assignments) {}

    public function index(Request $request): View
    {
        return view('training.assignments.index', [
            'rows' => $this->assignments->rowsForUser($request->user()),
        ]);
    }

    public function manage(Request $request): View
    {
        abort_unless($request->user()->can('manage training'), 403);

        return view('training.assignments.manage');
    }
}
