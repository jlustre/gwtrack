<?php

namespace App\Livewire\Training;

use App\Models\TrainingAssignment;
use App\Models\User;
use App\Services\Training\TrainingAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AssignmentManager extends Component
{
    public ?int $userId = null;

    public ?int $moduleId = null;

    public ?string $dueAt = null;

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        $defaultDays = config('training-academy.assignments.default_due_days');
        $this->dueAt = $defaultDays
            ? now()->addDays((int) $defaultDays)->format('Y-m-d')
            : null;
    }

    public function assign(TrainingAssignmentService $assignments): void
    {
        $this->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'moduleId' => ['required', 'integer', 'exists:training_modules,id'],
            'dueAt' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::query()->findOrFail($this->userId);
        $module = $assignments->assignableModules()->firstWhere('id', $this->moduleId);

        abort_unless($module, 422);

        $assignments->assign(
            $user,
            $module,
            auth()->user(),
            $this->dueAt ? Carbon::parse($this->dueAt) : null,
            $this->notes !== '' ? $this->notes : null,
        );

        session()->flash('assignment_status', 'assigned');
        $this->reset(['userId', 'moduleId', 'notes']);
        $defaultDays = config('training-academy.assignments.default_due_days');
        $this->dueAt = $defaultDays
            ? now()->addDays((int) $defaultDays)->format('Y-m-d')
            : null;
    }

    public function cancel(int $assignmentId, TrainingAssignmentService $assignments): void
    {
        $assignment = TrainingAssignment::query()->findOrFail($assignmentId);
        $assignments->cancel($assignment, auth()->user());
        session()->flash('assignment_status', 'cancelled');
    }

    public function render(TrainingAssignmentService $assignments): View
    {
        $recentAssignments = TrainingAssignment::query()
            ->with(['user', 'module', 'assignedBy'])
            ->latest('updated_at')
            ->limit(25)
            ->get();

        return view('livewire.training.assignment-manager', [
            'users' => $assignments->assignableUsers(),
            'modules' => $assignments->assignableModules(),
            'recentAssignments' => $recentAssignments,
        ]);
    }
}
