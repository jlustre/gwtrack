<?php

namespace App\Livewire\Training;

use App\Services\Training\TrainingPathService;
use App\Services\Training\TrainingRecommendationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LearningPlan extends Component
{
    public function dismiss(int $recommendationId, TrainingRecommendationService $recommendations): void
    {
        $recommendation = \App\Models\TrainingRecommendation::query()
            ->where('user_id', auth()->id())
            ->findOrFail($recommendationId);

        $recommendations->dismiss($recommendation, auth()->user());
        session()->flash('plan_status', 'dismissed');
    }

    public function enrollPath(string $pathCode, TrainingPathService $paths): void
    {
        $path = \App\Models\TrainingPath::query()->where('code', $pathCode)->where('is_active', true)->firstOrFail();
        $paths->enroll(auth()->user(), $path);
        session()->flash('plan_status', 'enrolled');
    }

    public function render(TrainingRecommendationService $recommendations): View
    {
        return view('livewire.training.learning-plan', [
            'plan' => $recommendations->learningPlanFor(auth()->user()),
        ]);
    }
}
