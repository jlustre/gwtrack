<?php

namespace App\Livewire\Admin\Training;

use App\Services\Training\TrainingAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminPathIndex extends Component
{
    public bool $showCreate = false;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $audience = 'associate';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);
    }

    public function createPath(TrainingAdminService $admin): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audience' => ['nullable', 'string', 'max:100'],
        ]);

        $path = $admin->createPath([
            'name' => $this->name,
            'code' => $this->code !== '' ? $this->code : null,
            'description' => $this->description,
            'audience' => $this->audience,
        ]);

        $this->redirect(route('admin.training.paths.show', $path), navigate: true);
    }

    public function render(TrainingAdminService $admin): View
    {
        return view('livewire.admin.training.admin-path-index', [
            'paths' => $admin->pathsForAdmin(),
            'audiences' => [
                'associate' => 'Associate',
                'mentor' => 'Mentor',
                'leader' => 'Leader',
            ],
        ]);
    }
}
