<?php

namespace App\Livewire\Training;

use App\Models\TrainingPath;
use App\Services\Training\TrainingPathService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PathDetail extends Component
{
    public TrainingPath $path;

    public function mount(TrainingPath $path): void
    {
        abort_unless($path->is_active, 404);

        $this->path = $path;
    }

    public function enroll(TrainingPathService $paths): void
    {
        $paths->enroll(auth()->user(), $this->path);
        session()->flash('path_status', 'enrolled');
    }

    public function render(TrainingPathService $paths): View
    {
        return view('livewire.training.path-detail', [
            'detail' => $paths->pathDetailFor(auth()->user(), $this->path),
        ]);
    }
}
