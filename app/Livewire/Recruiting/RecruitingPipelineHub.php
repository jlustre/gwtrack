<?php

namespace App\Livewire\Recruiting;

use App\Models\Prospect;
use App\Services\RecruitingPipelineService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RecruitingPipelineHub extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
        $this->authorize('viewAny', Prospect::class);
    }

    public function render(RecruitingPipelineService $pipeline): View
    {
        return view('livewire.recruiting.recruiting-pipeline-hub', [
            'pipeline' => $pipeline->dashboardFor(auth()->user()),
        ]);
    }
}
