<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackerChecklistController extends Controller
{
    public function __construct(private readonly ChecklistService $checklists) {}

    public function licensing(Request $request): View
    {
        return $this->show($request, $this->config('licensing'));
    }

    public function updateLicensing(Request $request, int $step): RedirectResponse
    {
        return $this->update($request, $this->config('licensing'), $step);
    }

    public function reviewLicensing(Request $request, int $progress): RedirectResponse
    {
        return $this->review($request, $this->config('licensing'), $progress);
    }

    public function apprenticeship(Request $request): View
    {
        return $this->show($request, $this->config('apprenticeship'));
    }

    public function updateApprenticeship(Request $request, int $step): RedirectResponse
    {
        return $this->update($request, $this->config('apprenticeship'), $step);
    }

    public function reviewApprenticeship(Request $request, int $progress): RedirectResponse
    {
        return $this->review($request, $this->config('apprenticeship'), $progress);
    }

    public function cfmTraining(Request $request): View
    {
        return $this->show($request, $this->config('cfm-training'));
    }

    public function updateCfmTraining(Request $request, int $step): RedirectResponse
    {
        return $this->update($request, $this->config('cfm-training'), $step);
    }

    public function reviewCfmTraining(Request $request, int $progress): RedirectResponse
    {
        return $this->review($request, $this->config('cfm-training'), $progress);
    }

    private function show(Request $request, array $config): View
    {
        $user = $request->user()->loadMissing(['profile', 'sponsor', 'mentor']);

        if (! $this->checklists->hasTypeStarted($user, $config['typeCode'])) {
            return view('checklists.not-started', $this->checklists->notStartedViewData(
                $user,
                $user,
                $config['typeCode'],
                $config['checklistTitle'] ?? $config['eyebrow'],
            ));
        }

        $steps = $this->checklists->activeSteps(
            $config['typeCode'],
            null,
            $config['groupLabel'] ?? null,
        );
        $typeStartDate = $this->checklists->typeStartDate($user, $config['typeCode']);
        $typeCompletionDueDate = $typeStartDate
            ? $this->checklists->typeCompletionDueDate($typeStartDate, $config['typeCode'])
            : null;
        $typeMaxCompleteDays = $this->checklists->maxCompleteDaysForType($config['typeCode']);

        $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));

        $steps = $this->checklists->enrichStepsWithSchedule(
            $steps->map(function ($step) use ($progress, $config) {
            $stepProgress = $progress->get($step->id);
            $step->progress = $stepProgress;
            $step->status = $stepProgress?->status ?? 'not_started';
            $step->is_completed = $step->status === 'completed';
            $step->is_pending = $step->status === 'pending_confirmation';
            $step->is_rejected = $step->status === 'rejected';
            $step->is_required = (bool) $step->is_required;
            $step->update_route = $config['updateRoute'];

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
        $confirmationItems = $this->checklists->confirmationItemsFor($user, $config['typeCode']);
        $needsConfirmation = $confirmationItems->count();

        return view($config['view'], [
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
            'tracker' => $config,
        ]);
    }

    private function update(Request $request, array $config, int $step): RedirectResponse
    {
        abort_unless($this->checklists->hasTypeStarted($request->user(), $config['typeCode']), 404);
        abort_unless($this->checklists->activeChecklistExists($step), 404);

        $completed = $request->boolean('completed');
        $this->checklists->updateUserProgress($request->user(), $step, $completed);

        return back()->with('status', $completed ? $config['statusPrefix'].'-item-submitted-for-confirmation' : $config['statusPrefix'].'-item-reopened');
    }

    private function review(Request $request, array $config, int $progress): RedirectResponse
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
                'message' => $confirmed
                    ? $config['statusPrefix'].' item confirmed.'
                    : $config['statusPrefix'].' item rejected.',
                'decision' => $validated['decision'],
            ]);
        }

        return back()->with('status', $confirmed ? $config['statusPrefix'].'-item-confirmed' : $config['statusPrefix'].'-item-rejected');
    }

    private function config(string $key): array
    {
        return match ($key) {
            'licensing' => [
                'key' => 'licensing',
                'typeCode' => 'licensing',
                'view' => 'licensing.index',
                'eyebrow' => 'Licensing Tracker',
                'title' => 'Move from preparation to approved field readiness',
                'description' => 'Track licensing milestones, course progress, exam readiness, application status, and leadership confirmation.',
                'checklistTitle' => 'Licensing Checklist',
                'checklistDescription' => 'Submit completed licensing milestones for sponsor, mentor, or agency confirmation.',
                'emptyTitle' => 'No licensing items available',
                'emptyDescription' => 'Your licensing checklist will appear when active licensing steps are available.',
                'itemCountLabel' => 'licensing items',
                'completedLabel' => 'licensing milestones complete',
                'graphLabel' => 'Overall licensing',
                'requiredLabel' => 'Required licensing',
                'statusPrefix' => 'licensing',
                'indexRoute' => 'licensing.index',
                'updateRoute' => 'licensing.update',
                'reviewRoute' => 'licensing.review',
            ],
            'apprenticeship' => [
                'key' => 'apprenticeship',
                'typeCode' => 'fap',
                'groupLabel' => 'Field Apprenticeship Program',
                'view' => 'apprenticeship.index',
                'eyebrow' => 'Field Apprenticeship',
                'title' => 'Build confidence through guided field activity',
                'description' => 'Track apprentice readiness, mentor-guided practice, supervised appointments, and FAP completion approval.',
                'checklistTitle' => 'Field Apprenticeship Checklist',
                'checklistDescription' => 'Submit FAP milestones for sponsor, CFM, trainer, or agency confirmation.',
                'emptyTitle' => 'No apprenticeship items available',
                'emptyDescription' => 'Your FAP checklist will appear when active apprenticeship steps are available.',
                'itemCountLabel' => 'FAP items',
                'completedLabel' => 'FAP milestones complete',
                'graphLabel' => 'Overall apprenticeship',
                'requiredLabel' => 'Required FAP completion',
                'statusPrefix' => 'apprenticeship',
                'indexRoute' => 'apprenticeship.index',
                'updateRoute' => 'apprenticeship.update',
                'reviewRoute' => 'apprenticeship.review',
            ],
            'cfm-training' => [
                'key' => 'cfm-training',
                'typeCode' => 'cfm-training',
                'view' => 'cfm-training.index',
                'eyebrow' => 'CFM Training',
                'title' => 'Prepare to mentor apprentices with excellence',
                'description' => 'Track Certified Field Mentor training modules, coaching standards, mentor documentation, and certification readiness.',
                'checklistTitle' => 'CFM Training Checklist',
                'checklistDescription' => 'Submit CFM training modules for sponsor, trainer, or agency confirmation.',
                'emptyTitle' => 'No CFM training items available',
                'emptyDescription' => 'Your CFM training checklist will appear when active training modules are available.',
                'itemCountLabel' => 'CFM training items',
                'completedLabel' => 'CFM modules complete',
                'graphLabel' => 'Overall CFM training',
                'requiredLabel' => 'Required CFM training',
                'statusPrefix' => 'cfm-training',
                'indexRoute' => 'cfm-training.index',
                'updateRoute' => 'cfm-training.update',
                'reviewRoute' => 'cfm-training.review',
            ],
        };
    }
}
