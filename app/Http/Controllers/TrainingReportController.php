<?php

namespace App\Http\Controllers;

use App\Services\Training\TrainingReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TrainingReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('training.reports.index');
    }

    public function download(Request $request, TrainingReportService $reports): Response
    {
        [$period, $scope] = $this->validatedFilters($request, $reports);

        return $reports->downloadPdf($request->user(), $period, $scope);
    }

    public function email(Request $request, TrainingReportService $reports): RedirectResponse
    {
        [$period, $scope] = $this->validatedFilters($request, $reports);

        $reports->sendEmail($request->user(), $period, $scope);

        return redirect()
            ->route('training.reports.index')
            ->with('training_report_status', 'Training report sent to your email.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validatedFilters(Request $request, TrainingReportService $reports): array
    {
        $validated = $request->validate([
            'period' => ['required', 'in:weekly,monthly,quarterly,annual'],
            'scope' => ['required', 'in:personal,directs,downline,organization'],
        ]);

        abort_unless($reports->canUseScope($request->user(), $validated['scope']), 403);

        return [$validated['period'], $validated['scope']];
    }
}
