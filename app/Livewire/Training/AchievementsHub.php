<?php

namespace App\Livewire\Training;

use App\Services\Training\TrainingGamificationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AchievementsHub extends Component
{
    public string $leaderboardScope = 'organization';

    public function render(TrainingGamificationService $gamification): View
    {
        $hub = $gamification->achievementsHubFor(auth()->user());

        $leaderboard = $this->leaderboardScope === 'team' && auth()->user()->team_id
            ? $hub['team_leaderboard']
            : $hub['leaderboard'];

        return view('livewire.training.achievements-hub', [
            'hub' => $hub,
            'leaderboard' => $leaderboard,
        ]);
    }
}
