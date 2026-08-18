<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChecklistService;
use App\Services\DownlineHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChecklistTypeStartController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function store(Request $request, User $user, string $typeCode): RedirectResponse
    {
        abort_unless($this->hierarchy->canViewMember($request->user(), $user), 403);
        abort_unless($this->checklists->canStartChecklistTypesFor($request->user(), $user), 403);
        abort_unless(in_array($typeCode, $this->checklists->memberFacingTypeCodes(), true), 404);

        $redirectTo = back()->getTargetUrl();

        return $this->startChecklist($request, $user, $typeCode, $redirectTo);
    }

    public function storeSelf(Request $request, string $typeCode): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($typeCode, $this->checklists->memberFacingTypeCodes(), true), 404);
        abort_unless($this->checklists->canStartChecklistTypesFor($user, $user), 403);

        $redirectTo = route($this->checklists->indexRouteForType($typeCode));

        return $this->startChecklist($request, $user, $typeCode, $redirectTo);
    }

    private function startChecklist(Request $request, User $user, string $typeCode, string $redirectTo): RedirectResponse
    {
        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'task_user' => ['nullable', 'integer', 'exists:task_users,id'],
        ]);

        try {
            $this->checklists->startChecklistType(
                $user,
                $typeCode,
                $request->user(),
                $validated['started_at'],
                isset($validated['task_user']) ? (int) $validated['task_user'] : null,
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->to($redirectTo)
            ->with('status', 'checklist-type-started');
    }
}
