<?php

namespace App\Livewire\Dashboard;

use App\Http\Controllers\TaskController;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class OpenTaskCountBadge extends Component
{
    public int $count = 0;

    public function mount(TaskController $tasks): void
    {
        $this->refreshCount($tasks);
    }

    #[On('invitation-email-sent')]
    #[On('invitation-deleted')]
    #[On('my-tasks-changed')]
    public function refreshCount(TaskController $tasks): void
    {
        $viewer = auth()->user();
        $this->count = $viewer ? $tasks->openTaskCountFor($viewer) : 0;
    }

    public function render(): View
    {
        return view('livewire.dashboard.open-task-count-badge');
    }
}
