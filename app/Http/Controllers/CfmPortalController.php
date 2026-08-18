<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCfmPortalCalendarSharingRequest;
use App\Http\Requests\UpdateCfmPortalProfileRequest;
use App\Services\CalendarShareService;
use App\Services\CfmAssignmentConfirmationTaskService;
use App\Services\CfmAssignmentWorkflowService;
use App\Services\CfmPortal\CfmProgressReportService;
use App\Services\CfmPortal\CfmRosterExportService;
use App\Services\CfmPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CfmPortalController extends Controller
{
    public function __construct(
        private readonly CfmPortalService $cfmPortal,
        private readonly CalendarShareService $calendarShare,
        private readonly CfmAssignmentWorkflowService $assignmentWorkflow,
        private readonly CfmAssignmentConfirmationTaskService $confirmationTasks,
    ) {}

    public function index(Request $request): View
    {
        $viewer = $request->user()->loadMissing(['profile', 'rank', 'team']);
        $cfmUserId = $request->integer('cfm') ?: null;

        $payload = $this->cfmPortal->payloadFor($viewer, $cfmUserId);

        return view('cfm-portal.index', [
            'user' => $viewer,
            'todayLabel' => now()->format('l, F j, Y'),
            'portal' => $payload,
            'openEditProfileModal' => (bool) session('open_edit_profile_modal', false),
        ]);
    }

    public function updateCalendarSharing(UpdateCfmPortalCalendarSharingRequest $request): RedirectResponse
    {
        $this->calendarShare->updateSharingSettings($request->user(), $request->validated());

        return redirect()
            ->route('cfm.portal')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => 'Calendar sharing preferences were updated.',
            ]);
    }

    public function updateProfile(UpdateCfmPortalProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['licensed_jurisdictions'] = $request->normalizedLicensedJurisdictions();

        $this->cfmPortal->updateProfile($request->user(), $validated);

        return redirect()
            ->route('cfm.portal')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => 'Your CFM profile was updated successfully.',
            ]);
    }

    public function confirmAssignment(Request $request, \App\Models\MentorAssignment $assignment): RedirectResponse
    {
        $this->assignmentWorkflow->confirmAssignment($assignment, $request->user());

        $assignment->loadMissing('apprentice');

        return redirect()
            ->route('cfm.portal')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => $assignment->apprentice->name.' is now your active trainee. Send your first welcome email from the trainee list.',
            ]);
    }

    public function reviewAssignment(Request $request, \App\Models\MentorAssignment $assignment): View|RedirectResponse
    {
        $assignment->loadMissing(['mentor', 'apprentice.sponsor', 'assignedBy', 'apprentice.profile', 'apprentice.rank']);
        $user = $request->user();

        if ($assignment->status !== 'pending') {
            $this->confirmationTasks->completeForAssignment($assignment);

            return redirect()
                ->route('cfm.portal')
                ->with('profile_feedback', [
                    'type' => 'info',
                    'message' => 'This trainee assignment has already been processed.',
                ]);
        }

        if ($assignment->mentor_id !== $user->id && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            abort(403);
        }

        return view('cfm-portal.review-assignment', [
            'assignment' => $assignment,
            'member' => $assignment->apprentice,
        ]);
    }

    public function sendFirstContact(Request $request, \App\Models\MentorAssignment $assignment): RedirectResponse
    {
        $this->assignmentWorkflow->sendFirstContactEmail($assignment, $request->user());

        $assignment->loadMissing('apprentice');

        return redirect()
            ->route('cfm.portal')
            ->with('profile_feedback', [
                'type' => 'success',
                'message' => 'Your first welcome email was sent to '.$assignment->apprentice->name.'.',
            ]);
    }

    public function downloadReport(Request $request, \App\Models\CfmProgressReport $report, CfmProgressReportService $reports): Response
    {
        $viewer = $request->user();
        $cfmUserId = $request->integer('cfm') ?: null;
        $cfm = app(CfmPortalService::class)->payloadFor($viewer, $cfmUserId)['cfmUser'];

        return $reports->downloadPdf($cfm, $report);
    }

    public function exportRoster(Request $request, CfmRosterExportService $exports): StreamedResponse
    {
        $viewer = $request->user();
        $cfmUserId = $request->integer('cfm') ?: null;
        $cfm = app(CfmPortalService::class)->payloadFor($viewer, $cfmUserId)['cfmUser'];

        return $exports->downloadCsv($cfm);
    }
}
