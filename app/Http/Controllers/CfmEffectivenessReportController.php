<?php

namespace App\Http\Controllers;

use App\Models\CfmEffectiveness\CfmEffectivenessReport;
use App\Services\CfmEffectiveness\CfmEffectivenessReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CfmEffectivenessReportController extends Controller
{
    public function download(Request $request, CfmEffectivenessReport $report, CfmEffectivenessReportService $reports): Response
    {
        return $reports->downloadPdf($request->user(), $report);
    }

    public function generate(Request $request, CfmEffectivenessReportService $reports): RedirectResponse
    {
        abort_unless($request->user()->can('view CFM effectiveness') || $request->user()->can('view CFM reports'), 403);

        $validated = $request->validate([
            'report_type' => ['required', 'in:'.implode(',', CfmEffectivenessReport::TYPES)],
            'period_type' => ['required', 'in:'.implode(',', CfmEffectivenessReport::PERIODS)],
            'audience' => ['nullable', 'in:'.implode(',', CfmEffectivenessReport::AUDIENCES)],
            'cfm_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $viewer = $request->user();
        $cfm = $reports->resolveCfm($viewer, $validated['cfm_id'] ?? null);

        $report = $reports->generate(
            $viewer,
            $cfm,
            $validated['report_type'],
            $validated['period_type'],
            $validated['audience'] ?? 'cfm',
        );

        return redirect()
            ->route('cfm.effectiveness.reports.download', $report)
            ->with('cfm_effectiveness_status', 'Report generated successfully.');
    }
}
