<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Models\GoalNote;
use App\Services\Goals\GoalAlertService;
use App\Services\Goals\GoalCoachingService;
use App\Services\Goals\GoalConversionRateService;
use App\Services\Goals\GoalForecastingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class CfmCoachingGoals extends Component
{
    use WithFileUploads;

    public ?int $selectedTraineeId = null;

    public string $coachNote = '';

    public ?int $selectedGoalId = null;

  public $coachAudio;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('coach goals'), 403);
    }

    public function saveCoachNote(): void
    {
        $this->validate([
            'selectedGoalId' => ['required', 'exists:goals,id'],
            'coachNote' => ['nullable', 'string', 'max:5000', 'required_without:coachAudio'],
            'coachAudio' => ['nullable', 'file', 'mimetypes:audio/webm,audio/mp4,audio/mpeg,audio/wav', 'max:10240', 'required_without:coachNote'],
        ]);

        $goal = Goal::query()->findOrFail($this->selectedGoalId);
        $this->authorize('view', $goal);

        $audioPath = null;

        if ($this->coachAudio) {
            $audioPath = $this->coachAudio->store('goal-notes/audio', 'public');
        }

        GoalNote::query()->create([
            'goal_id' => $goal->id,
            'author_id' => auth()->id(),
            'note_type' => 'coach',
            'body' => $this->coachNote ?: ($audioPath ? '(Voice note)' : ''),
            'audio_path' => $audioPath,
            'is_private' => false,
        ]);

        $this->reset(['coachNote', 'selectedGoalId', 'coachAudio']);
        session()->flash('goals_status', 'Coach note saved.');
    }

    public function render(
        GoalCoachingService $coaching,
        GoalConversionRateService $conversions,
        GoalForecastingService $forecasting,
        GoalAlertService $alerts,
    ): View {
        $coach = auth()->user();
        $traineeGoals = $coaching->traineeGoalsFor($coach);

        $filtered = $this->selectedTraineeId
            ? $traineeGoals->where('user_id', $this->selectedTraineeId)
            : $traineeGoals;

        $trainees = $traineeGoals->pluck('user')->unique('id')->values();

        $traineeInsights = $trainees->mapWithKeys(function ($trainee) use ($forecasting, $alerts, $conversions) {
            if (! $trainee) {
                return [];
            }

            return [$trainee->id => [
                'forecasts' => $forecasting->forecastSummaryFor($trainee),
                'alerts' => $alerts->evaluateUser($trainee)->take(3),
                'conversion_kpis' => $conversions->kpiLabelsForFunnel('income', $trainee),
            ]];
        });

        return view('livewire.goals.cfm-coaching-goals', [
            'traineeGoals' => $filtered,
            'trainees' => $trainees,
            'suggestions' => $coaching->suggestionsFor($coach),
            'traineeInsights' => $traineeInsights,
        ]);
    }
}
