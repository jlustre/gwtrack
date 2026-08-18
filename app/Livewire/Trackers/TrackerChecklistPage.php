<?php

namespace App\Livewire\Trackers;

use App\Models\Checklist;
use App\Services\ChecklistService;
use App\Services\NewMemberOnboardingTaskService;
use App\Support\TrackerChecklistConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class TrackerChecklistPage extends Component
{
    public string $trackerKey = 'onboarding';

    #[Url(as: 'task_user')]
    public ?int $taskUserId = null;

    public string $startedAt = '';

    public ?string $statusMessage = null;

    public function mount(?string $trackerKey = null): void
    {
        abort_unless(auth()->check(), 403);

        $this->trackerKey = filled($trackerKey)
            ? $trackerKey
            : TrackerChecklistConfig::keyFromRoute(request()->route()?->getName());

        abort_unless(in_array($this->trackerKey, ['onboarding', 'licensing', 'apprenticeship', 'cfm-training'], true), 404);

        $this->startedAt = now()->toDateString();

        if ($this->taskUserId === null && request()->filled('task_user')) {
            $this->taskUserId = request()->integer('task_user') ?: null;
        }
    }

    public function toggleStep(int $stepId, bool $completed, ChecklistService $checklists): void
    {
        $user = auth()->user();
        $config = TrackerChecklistConfig::for($this->trackerKey);

        abort_unless($checklists->hasTypeStarted($user, $config['typeCode']), 404);
        abort_unless($checklists->activeChecklistExists($stepId), 404);

        if ($config['usesCountryFilter'] ?? false) {
            $step = Checklist::query()->findOrFail($stepId);
            $country = $user->loadMissing('profile.countryRecord')->profile?->country;
            abort_unless($checklists->onboardingChecklistApplicable($step, $country), 404);
        }

        $checklists->updateUserProgress($user, $stepId, $completed);

        $this->statusMessage = $completed
            ? $config['statusPrefix'].'-item-submitted-for-confirmation'
            : $config['statusPrefix'].'-item-reopened';
    }

    public function startChecklist(ChecklistService $checklists): void
    {
        $user = auth()->user();
        $config = TrackerChecklistConfig::for($this->trackerKey);

        abort_unless($checklists->canStartChecklistTypesFor($user, $user), 403);

        $this->validate([
            'startedAt' => ['required', 'date'],
        ]);

        try {
            $checklists->startChecklistType(
                $user,
                $config['typeCode'],
                $user,
                $this->startedAt,
                $this->taskUserId,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'started_at' ? 'startedAt' : $field, $message);
                }
            }

            return;
        }

        $this->statusMessage = 'checklist-type-started';
    }

    public function render(
        ChecklistService $checklists,
        NewMemberOnboardingTaskService $onboardingTasks,
    ): View {
        $user = auth()->user()->loadMissing([
            'profile.countryRecord',
            'sponsor',
            'mentor',
        ]);
        $config = TrackerChecklistConfig::for($this->trackerKey);

        if (! $checklists->hasTypeStarted($user, $config['typeCode'])) {
            return view('livewire.trackers.tracker-checklist-page', [
                'tracker' => $config,
                'started' => false,
                'notStarted' => $checklists->notStartedViewData(
                    $user,
                    $user,
                    $config['typeCode'],
                    $config['checklistTitle'] ?? $config['eyebrow'],
                ),
                'user' => $user,
                'steps' => collect(),
                'stats' => [],
                'typeStartDate' => null,
                'typeCompletionDueDate' => null,
                'typeMaxCompleteDays' => null,
            ])->layout('layouts.app');
        }

        if ($this->trackerKey === 'onboarding') {
            $onboardingTasks->completeIfOpen($user, $this->taskUserId);
        }

        $country = ($config['usesCountryFilter'] ?? false) ? $user->profile?->country : null;
        $steps = $checklists->activeSteps(
            $config['typeCode'],
            $country,
            $config['groupLabel'] ?? null,
        );
        $typeStartDate = $checklists->typeStartDate($user, $config['typeCode']);
        $typeCompletionDueDate = $typeStartDate
            ? $checklists->typeCompletionDueDate($typeStartDate, $config['typeCode'])
            : null;
        $typeMaxCompleteDays = $checklists->maxCompleteDaysForType($config['typeCode']);
        $progress = $checklists->userProgressFor($user->id, $steps->pluck('id'));

        $steps = $checklists->enrichStepsWithSchedule(
            $steps->map(function (Checklist $step) use ($progress): Checklist {
                $stepProgress = $progress->get($step->id);
                $step->progress = $stepProgress;
                $step->status = $stepProgress?->status ?? 'not_started';
                $step->is_completed = $step->status === 'completed';
                $step->is_pending = $step->status === 'pending_confirmation';
                $step->is_rejected = $step->status === 'rejected';
                $step->is_required = (bool) $step->is_required;

                return $step;
            }),
            $typeStartDate,
        );

        return view('livewire.trackers.tracker-checklist-page', [
            'tracker' => $config,
            'started' => true,
            'notStarted' => null,
            'user' => $user,
            'steps' => $steps,
            'typeStartDate' => $typeStartDate,
            'typeCompletionDueDate' => $typeCompletionDueDate,
            'typeMaxCompleteDays' => $typeMaxCompleteDays,
            'stats' => $this->statsFor($steps),
        ])->layout('layouts.app');
    }

    /**
     * @param  Collection<int, Checklist>  $steps
     * @return array<string, int>
     */
    private function statsFor(Collection $steps): array
    {
        $total = $steps->count();
        $completed = $steps->where('is_completed', true)->count();
        $pending = $steps->where('is_pending', true)->count();
        $rejected = $steps->where('is_rejected', true)->count();
        $requiredTotal = $steps->where('is_required', true)->count();
        $requiredCompleted = $steps->where('is_required', true)->where('is_completed', true)->count();
        $optionalTotal = $total - $requiredTotal;
        $optionalCompleted = $completed - $requiredCompleted;
        $remaining = max(0, $total - $completed);
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $requiredPercent = $requiredTotal > 0 ? (int) round(($requiredCompleted / $requiredTotal) * 100) : 0;

        return compact(
            'total',
            'completed',
            'pending',
            'rejected',
            'remaining',
            'percent',
            'requiredTotal',
            'requiredCompleted',
            'requiredPercent',
            'optionalTotal',
            'optionalCompleted',
        ) + ['needsConfirmation' => $pending];
    }
}
