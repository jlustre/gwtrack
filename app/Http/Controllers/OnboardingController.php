<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Services\ChecklistService;
use App\Services\NewMemberOnboardingTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklists,
        private readonly NewMemberOnboardingTaskService $onboardingTasks,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing([
            'profile.countryRecord',
            'sponsor',
            'mentor',
        ]);
        $taskUserId = $request->integer('task_user') ?: null;
        $country = $user->profile?->country;

        if (! $this->checklists->hasTypeStarted($user, 'onboarding')) {
            return view('checklists.not-started', array_merge(
                $this->checklists->notStartedViewData(
                    $user,
                    $user,
                    'onboarding',
                    'Onboarding',
                ),
                ['taskUserId' => $taskUserId],
            ));
        }

        $this->onboardingTasks->completeIfOpen($user, $taskUserId);

        $steps = $this->checklists->activeSteps('onboarding', $country);
        $typeStartDate = $this->checklists->typeStartDate($user, 'onboarding');
        $typeCompletionDueDate = $typeStartDate
            ? $this->checklists->typeCompletionDueDate($typeStartDate, 'onboarding')
            : null;
        $typeMaxCompleteDays = $this->checklists->maxCompleteDaysForType('onboarding');
        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));

        $steps = $this->checklists->enrichStepsWithSchedule(
            $steps->map(function (Checklist $step) use ($progress): Checklist {
            $stepProgress = $progress->get($step->id);
            $step->progress = $stepProgress;
            $step->status = $stepProgress?->status ?? 'not_started';
            $step->is_completed = $step->status === 'completed';
            $step->is_pending = $step->status === 'pending_confirmation';
            $step->is_rejected = $step->status === 'rejected';

            return $step;
            }),
            $typeStartDate,
        );

        $total = $steps->count();
        $completed = $steps->where('is_completed', true)->count();
        $pending = $steps->where('is_pending', true)->count();
        $rejected = $steps->where('is_rejected', true)->count();
        $requiredTotal = $steps->where('is_required', true)->count();
        $requiredCompleted = $steps
            ->where('is_required', true)
            ->where('is_completed', true)
            ->count();
        $optionalTotal = $total - $requiredTotal;
        $optionalCompleted = $completed - $requiredCompleted;
        $remaining = max(0, $total - $completed);
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $requiredPercent = $requiredTotal > 0 ? (int) round(($requiredCompleted / $requiredTotal) * 100) : 0;
        $confirmationItems = $this->checklists->confirmationItemsFor($user, 'onboarding');
        $needsConfirmation = $confirmationItems->count();

        return view('onboarding.index', [
            'user' => $user,
            'steps' => $steps,
            'typeStartDate' => $typeStartDate,
            'typeCompletionDueDate' => $typeCompletionDueDate,
            'typeMaxCompleteDays' => $typeMaxCompleteDays,
            'confirmationItems' => $confirmationItems,
            'stats' => compact(
                'total',
                'completed',
                'pending',
                'rejected',
                'needsConfirmation',
                'remaining',
                'percent',
                'requiredTotal',
                'requiredCompleted',
                'requiredPercent',
                'optionalTotal',
                'optionalCompleted'
            ),
        ]);
    }

    public function update(Request $request, Checklist $step): RedirectResponse
    {
        abort_if($step->trashed() || ! $step->is_active, 404);
        abort_unless($this->checklists->hasTypeStarted($request->user(), 'onboarding'), 404);

        $country = $request->user()->loadMissing('profile.countryRecord')->profile?->country;
        abort_unless($this->checklists->onboardingChecklistApplicable($step, $country), 404);

        $completed = $request->boolean('completed');
        $this->checklists->updateUserProgress($request->user(), $step->id, $completed);

        return back()->with('status', $completed ? 'onboarding-item-submitted-for-confirmation' : 'onboarding-item-reopened');
    }

    public function review(Request $request, int $progress): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:confirmed,rejected'],
            'review_comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->checklists->reviewUserProgress(
            $request->user(),
            $progress,
            $validated['decision'],
            $validated['review_comments'] ?? null,
        );

        $confirmed = $validated['decision'] === 'confirmed';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $confirmed ? 'Onboarding item confirmed.' : 'Onboarding item rejected.',
                'decision' => $validated['decision'],
            ]);
        }

        return back()->with('status', $confirmed ? 'onboarding-item-confirmed' : 'onboarding-item-rejected');
    }
}
