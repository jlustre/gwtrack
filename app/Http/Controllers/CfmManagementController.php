<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCfmAssignmentRequest;
use App\Http\Requests\StoreCfmNominationRequest;
use App\Http\Requests\UpdateCfmLicensedJurisdictionsRequest;
use App\Models\User;
use App\Services\CfmManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CfmManagementController extends Controller
{
    public function __construct(private readonly CfmManagementService $cfmManagement) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing(['profile', 'rank', 'team']);

        return view('team.cfms.index', [
            'user' => $user,
            'todayLabel' => now()->format('l, F j, Y'),
            'cfmManagementPayload' => $this->cfmManagement->payloadFor($user),
            'rankStructure' => $this->cfmManagement->rankStructureFor(),
            'openAssignModal' => (bool) session('open_assign_modal', false),
            'openCfmProfilePanel' => (bool) session('open_cfm_profile_panel', false),
            'openCfmLicensedEdit' => (bool) session('open_cfm_licensed_edit', false),
            'focusCfmId' => session('focus_cfm_id') ?? $request->integer('cfm') ?: null,
            'cfmLicensedFeedback' => session('cfm_licensed_feedback'),
        ]);
    }

    public function updateLicensedJurisdictions(UpdateCfmLicensedJurisdictionsRequest $request, User $user): RedirectResponse
    {
        $result = $this->cfmManagement->updateLicensedJurisdictions(
            $request->user(),
            $user,
            $request->normalizedLicensedJurisdictions()
        );

        return redirect()
            ->route('team.cfms', ['cfm' => $user->id])
            ->with('open_cfm_profile_panel', true)
            ->with('focus_cfm_id', $user->id)
            ->with('cfm_licensed_feedback', [
                'type' => 'success',
                'message' => $result['message'],
            ]);
    }

    public function assign(StoreCfmAssignmentRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->cfmManagement->assignAssociate(
                $request->user(),
                $request->validated()
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()
                ->route('team.cfms')
                ->withErrors($exception->errors())
                ->withInput()
                ->with('open_assign_modal', true);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('team.cfms')
            ->with('status', $result['message']);
    }

    public function store(StoreCfmNominationRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->cfmManagement->addCfm(
                $request->user(),
                $request->validated()
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()
                ->route('team.cfms')
                ->withErrors($exception->errors())
                ->withInput()
                ->with('open_add_cfm_modal', true);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('team.cfms')
            ->with('status', $result['message']);
    }
}
