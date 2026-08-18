<?php

namespace App\Livewire\Dashboard;

use App\Services\Teams\CreateMyTeamService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateMyTeamModal extends Component
{
    public bool $show = false;

    public string $name = '';

    public string $description = '';

    public int $hierarchyCount = 0;

    public ?string $statusMessage = null;

    #[On('open-create-my-team-modal')]
    public function open(CreateMyTeamService $teams): void
    {
        $user = auth()->user();
        abort_unless($user && $teams->userMayCreate($user), 403);

        $this->resetValidation();
        $this->reset(['name', 'description', 'statusMessage']);
        $this->hierarchyCount = $teams->hierarchyMemberCount($user);
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
        $this->reset(['name', 'description', 'statusMessage']);
    }

    public function create(CreateMyTeamService $teams): void
    {
        $user = auth()->user();
        abort_unless($user && $teams->userMayCreate($user), 403);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $teams->create(
                $user,
                $this->name,
                $this->description !== '' ? $this->description : null,
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->addError('name', $message ?: 'Unable to create team.');

            return;
        }

        $team = $result['team'];
        $moved = $result['members_moved'];

        session()->flash(
            'status',
            $moved > 0
                ? "Team \"{$team->name}\" created. {$moved} recruit(s) in your hierarchy were moved onto your team."
                : "Team \"{$team->name}\" created. You are the team leader — new recruits will join this team when they register under you."
        );

        $this->close();

        $this->redirect(route('team.index'), navigate: true);
    }

    public function render(CreateMyTeamService $teams): View
    {
        $user = auth()->user();

        return view('livewire.dashboard.create-my-team-modal', [
            'canCreate' => $user ? $teams->userMayCreate($user) : false,
        ]);
    }
}
