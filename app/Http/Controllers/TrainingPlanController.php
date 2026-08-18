<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingPlanController extends Controller
{
    public function index(Request $request): View
    {
        return view('training.plan.index');
    }
}
