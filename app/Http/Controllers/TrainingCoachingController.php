<?php

namespace App\Http\Controllers;

use App\Services\Training\TrainingCoachingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingCoachingController extends Controller
{
    public function __construct(private readonly TrainingCoachingService $coaching) {}

    public function index(Request $request): View
    {
        return view('training.coaching.index', [
            'hub' => $this->coaching->hubFor($request->user()),
        ]);
    }
}
