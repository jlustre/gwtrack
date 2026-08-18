<?php

namespace App\Http\Controllers;

use App\Models\MentorAssignment;
use App\Services\CfmAssignmentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CfmAssignmentConfirmationController extends Controller
{
    public function __construct(
        private readonly CfmAssignmentWorkflowService $workflow,
    ) {}

    public function show(Request $request, MentorAssignment $assignment): View|RedirectResponse
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        if ($assignment->status !== 'pending') {
            return redirect()
                ->route('cfm.portal')
                ->with('profile_feedback', [
                    'type' => 'info',
                    'message' => 'This assignment has already been processed.',
                ]);
        }

        if ($request->user() && $request->user()->id === $assignment->mentor_id) {
            return $this->confirm($request, $assignment);
        }

        return view('cfm-portal.confirm-assignment', [
            'assignment' => $assignment,
            'loginUrl' => route('login'),
        ]);
    }

    public function confirm(Request $request, MentorAssignment $assignment): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()
                ->guest(route('login'))
                ->with('url.intended', $request->fullUrl());
        }

        $this->workflow->confirmAssignment($assignment, $user);

        return redirect()
            ->route('cfm.portal')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => $assignment->apprentice->name.' is now your active trainee. Send your first welcome email from the portal.',
            ]);
    }
}
