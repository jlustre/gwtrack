<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingModule;
use App\Models\TrainingPath;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTrainingController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        return view('admin.training.index');
    }

    public function management(): View
    {
        abort_unless(auth()->user()->can('manage training enrollments'), 403);

        return view('admin.training.management');
    }

    public function courses(): View
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        return view('admin.training.courses.index');
    }

    public function course(TrainingModule $module): View
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        return view('admin.training.courses.show', compact('module'));
    }

    public function paths(): View
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        return view('admin.training.paths.index');
    }

    public function path(TrainingPath $path): View
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        return view('admin.training.paths.show', compact('path'));
    }
}
