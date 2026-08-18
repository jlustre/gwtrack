<?php

namespace App\Livewire\Admin\Training;

use App\Services\Training\TrainingAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminTrainingHub extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);
    }

    public function render(TrainingAdminService $admin): View
    {
        return view('livewire.admin.training.admin-training-hub', [
            'stats' => $admin->hubStats(),
        ]);
    }
}
