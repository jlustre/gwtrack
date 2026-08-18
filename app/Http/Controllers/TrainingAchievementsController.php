<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingAchievementsController extends Controller
{
    public function index(Request $request): View
    {
        return view('training.achievements.index');
    }
}
