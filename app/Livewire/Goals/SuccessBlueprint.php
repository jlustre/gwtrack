<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Models\GoalBlueprint;
use App\Services\Goals\GoalBlueprintService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SuccessBlueprint extends Component
{
    public GoalBlueprint $blueprint;

    public function mount(GoalBlueprint $blueprint): void
    {
        abort_unless($blueprint->user_id === auth()->id(), 403);
        $this->blueprint = $blueprint;
    }

    public function render(GoalBlueprintService $blueprints): View
    {
        $data = $blueprints->blueprintView($this->blueprint);

        return view('livewire.goals.success-blueprint', $data);
    }
}
