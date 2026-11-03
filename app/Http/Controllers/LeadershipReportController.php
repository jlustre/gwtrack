<?php

namespace App\Http\Controllers;

use App\Services\LeadershipReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadershipReportController extends Controller
{
    public function download(Request $request, LeadershipReportService $reports): StreamedResponse
    {
        abort_unless($request->user()->can('export team data'), 403);

        $validated = $request->validate([
            'period' => ['required', 'in:monthly,quarterly,annual'],
            'team_id' => ['nullable', 'integer'],
            'rank_id' => ['nullable', 'integer'],
            'role' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $report = $reports->build($request->user(), $validated['period'], limit: null, filters: [
            'team_id' => (string) ($validated['team_id'] ?? ''),
            'rank_id' => (string) ($validated['rank_id'] ?? ''),
            'role' => $validated['role'] ?? '',
            'status' => $validated['status'] ?? '',
        ]);
        $filename = 'leadership-report-'.$report['period'].'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                throw new \RuntimeException('Unable to open the report output stream.');
            }

            fputcsv($stream, [
                'Member',
                'Rank',
                'Team',
                'Status',
                'Joined',
                'Onboarding completion (%)',
                'Licensing completion (%)',
                'Apprenticeship completion (%)',
                'Active apprentices',
                'Lessons completed',
                'Assessments passed',
                'Rank progress (%)',
            ]);

            foreach ($report['member_rows'] as $row) {
                fputcsv($stream, [
                    $row['name'],
                    $row['rank'],
                    $row['team'],
                    $row['status'],
                    $row['joined'],
                    $row['onboarding'],
                    $row['licensing'],
                    $row['apprenticeship'],
                    $row['active_apprentices'],
                    $row['lessons_completed'],
                    $row['assessments_passed'],
                    $row['rank_progress'],
                ]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
