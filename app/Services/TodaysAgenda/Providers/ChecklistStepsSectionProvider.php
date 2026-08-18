<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

class ChecklistStepsSectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    public function __construct(
        private readonly ChecklistService $checklists,
    ) {}

    public function key(): string
    {
        return 'checklists';
    }

    public function label(): string
    {
        return 'Checklist Steps';
    }

    public function icon(): string
    {
        return 'list-checks';
    }

    public function sort(): int
    {
        return 50;
    }

    public function isEnabledFor(User $user): bool
    {
        return true;
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $items = [];
        $typeLabels = config('todays-agenda.checklist_type_labels', []);

        foreach ($this->checklists->memberFacingTypeCodes() as $typeCode) {
            if (! $this->checklists->hasTypeStarted($user, $typeCode)) {
                continue;
            }

            $startDate = $this->checklists->typeStartDate($user, $typeCode);

            if (! $startDate) {
                continue;
            }

            $steps = $this->checklists->activeSteps($typeCode, $user->profile?->country);
            $steps = $this->checklists->enrichStepsWithSchedule($steps, $startDate);
            $progress = $this->checklists->userProgressFor($user->id, $steps->pluck('id'));
            $routeName = $this->checklists->indexRouteForType($typeCode);
            $typeLabel = $typeLabels[$typeCode] ?? str($typeCode)->headline()->toString();

            foreach ($steps as $step) {
                $status = $progress->get($step->id)?->status ?? 'not_started';

                if ($status === 'completed') {
                    continue;
                }

                $dueDate = $step->expected_due_date ?? null;
                $isOverdue = $dueDate?->copy()->startOfDay()->lt($dayStart->copy()->startOfDay()) ?? false;

                $items[] = [
                    'id' => 'checklist-'.$typeCode.'-'.$step->id,
                    'title' => $step->title,
                    'subtitle' => $typeLabel,
                    'meta' => $dueDate
                        ? ($isOverdue
                            ? 'Overdue · '.$dueDate->format('M j, Y')
                            : ($dueDate->isSameDay($dayStart) ? 'Due today' : 'Due '.$dueDate->format('M j, Y')))
                        : 'Next step',
                    'url' => Route::has($routeName) ? route($routeName) : null,
                    'badge' => $isOverdue ? 'Overdue' : 'Checklist',
                    'highlight' => $isOverdue || ($dueDate?->isSameDay($dayStart) ?? false),
                    'sort_at' => ($dueDate ?? $dayStart)->startOfDay()->toIso8601String(),
                ];
            }
        }

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No open checklist steps.',
            Route::has('onboarding.index') ? ['label' => 'View trackers', 'url' => route('onboarding.index')] : null,
        );
    }
}
