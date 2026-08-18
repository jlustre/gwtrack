<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\MentorAssignment;
use App\Services\CfmTraineeChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CfmTraineeChecklistController extends Controller
{
    public function __construct(
        private readonly CfmTraineeChecklistService $checklist,
    ) {}

    public function show(Request $request, MentorAssignment $assignment): View|JsonResponse
    {
        $this->checklist->ensureAssignmentAccess($assignment, $request->user());

        $payload = $this->checklist->checklistForAssignment($assignment);

        if ($request->expectsJson()) {
            return response()->json(
                array_merge(
                    $this->checklist->checklistJsonForAssignment($assignment),
                    ['checklist_url' => route('cfm.portal.trainees.checklist', $assignment)],
                )
            );
        }

        return view('cfm-portal.trainee-checklist', [
            'user' => $request->user(),
            'payload' => $payload,
        ]);
    }

    public function update(Request $request, MentorAssignment $assignment, Checklist $item): RedirectResponse
    {
        $validated = $request->validate([
            'completed' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->checklist->updateProgress(
            $assignment,
            $item,
            $request->user(),
            (bool) $validated['completed'],
            $validated['notes'] ?? null,
        );

        return redirect()
            ->route('cfm.portal.trainees.checklist', $assignment)
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => $validated['completed']
                    ? 'Checklist item marked complete.'
                    : 'Checklist item marked incomplete.',
            ]);
    }
}
