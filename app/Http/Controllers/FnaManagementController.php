<?php

namespace App\Http\Controllers;

use App\Models\FnaRecord;
use App\Models\Prospect;
use App\Services\Fna\FnaExportService;
use App\Services\Fna\FnaRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FnaManagementController extends Controller
{
    public function __construct(
        private FnaRecordService $records,
        private FnaExportService $export,
    ) {}

    public function dashboard(Request $request): View
    {
        return view('team.fna.dashboard');
    }

    public function index(Request $request): View
    {
        return view('team.fna.index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FnaRecord::class);

        return view('team.fna.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', FnaRecord::class);

        $prospect = null;
        if ($request->filled('prospect_id')) {
            $prospect = Prospect::query()->findOrFail($request->string('prospect_id'));
            $this->authorize('view', $prospect);
        }

        $fna = $this->records->create($request->user(), [
            'client_name' => $request->string('client_name')->toString() ?: null,
            'title' => $request->string('title')->toString() ?: null,
        ], $prospect);

        return redirect()
            ->route('team.fna.wizard', $fna)
            ->with('fna_status', 'FNA draft created. Continue in the wizard.');
    }

    public function show(Request $request, FnaRecord $fnaRecord): View
    {
        $this->authorize('view', $fnaRecord);

        $fnaRecord->load([
            'prospect',
            'cfm',
            'owner',
            'household',
            'incomeDetail',
            'debtDetail',
            'assetDetail',
            'existingCoverage',
            'goals',
            'riskAssessment',
            'dimeAnalysis',
            'statusHistories.changedBy',
            'reviewComments.user',
        ]);

        return view('team.fna.show', ['fna' => $fnaRecord]);
    }

    public function edit(Request $request, FnaRecord $fnaRecord): View
    {
        $this->authorize('update', $fnaRecord);

        return view('team.fna.edit', ['fna' => $fnaRecord]);
    }

    public function wizard(Request $request, FnaRecord $fnaRecord): View
    {
        $this->authorize('view', $fnaRecord);

        return view('team.fna.wizard', ['fna' => $fnaRecord]);
    }

    public function dimeCalculator(Request $request): View
    {
        return view('team.fna.dime-calculator', [
            'prefillFnaId' => $request->query('fna'),
        ]);
    }

    public function export(Request $request, FnaRecord $fnaRecord): View
    {
        $this->authorize('export', $fnaRecord);

        return view('team.fna.export', ['fna' => $fnaRecord]);
    }

    public function exportDownload(Request $request, FnaRecord $fnaRecord): Response
    {
        $this->authorize('export', $fnaRecord);

        return $this->export->downloadPdf($fnaRecord, $request->user());
    }

    public function cfmReviewQueue(Request $request): View
    {
        abort_unless($request->user()->can('review trainee fna records'), 403);

        return view('team.fna.cfm-review-queue');
    }

    public function agencyReports(Request $request): View
    {
        abort_unless($request->user()->can('view fna agency reports'), 403);

        return view('team.fna.agency-reports');
    }

    public function prospectFnas(Request $request, Prospect $prospect): View
    {
        $this->authorize('view', $prospect);

        $records = FnaRecord::query()
            ->where('prospect_id', $prospect->id)
            ->where(function ($query) use ($request, $prospect): void {
                $query->where('owner_user_id', $request->user()->id);

                if ($request->user()->can('review trainee fna records')) {
                    $query->orWhere('owner_user_id', $prospect->owner_id);
                }
            })
            ->latest()
            ->get();

        return view('team.fna.prospect-index', compact('prospect', 'records'));
    }
}
