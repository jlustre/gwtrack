<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $view = match (true) {
            $user->hasRole('super-admin') => 'admin.dashboards.super-admin',
            $user->hasRole('admin') => 'admin.dashboards.super-admin',
            $user->hasRole('agency-owner') => 'admin.dashboards.agency-owner',
            $user->hasRole('team-leader') => 'admin.dashboards.team-leader',
            $user->hasRole('certified-field-mentor') => 'admin.dashboards.cfm',
            $user->hasRole('trainer') => 'admin.dashboards.trainer',
            default => abort(403),
        };

        return view($view);
    }
}
