<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\Goals\GoalReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GoalReportController extends Controller
{
    public function download(Request $request, GoalReportService $reports): Response
    {
        $this->authorize('viewAny', Goal::class);

        $period = $this->validatedPeriod($request);

        return $reports->downloadPdf($request->user(), $period);
    }

    public function email(Request $request, GoalReportService $reports): RedirectResponse
    {
        $this->authorize('viewAny', Goal::class);

        $period = $this->validatedPeriod($request);

        $reports->sendEmail($request->user(), $period);

        return redirect()
            ->route('goals.index', ['tab' => 'reports'])
            ->with('goals_status', 'Performance report sent to your email.');
    }

    private function validatedPeriod(Request $request): string
    {
        return $request->validate([
            'period' => ['required', 'in:weekly,monthly,quarterly,annual'],
        ])['period'];
    }
}
