<?php

namespace App\Livewire\Training;

use App\Models\User;
use App\Services\Training\TrainingCoachingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class CoachingCenter extends Component
{
    public ?int $traineeId = null;

    public string $reviewType = 'coaching';

    public ?int $score = null;

    public string $feedback = '';

    public string $sessionTitle = '';

    public string $sessionDescription = '';

    public string $sessionType = 'live';

    public ?string $sessionStartsAt = null;

    public ?int $sessionCapacity = null;

    public function mount(): void
    {
        $this->sessionStartsAt = now()->addWeek()->format('Y-m-d\TH:i');
    }

    public function submitReview(TrainingCoachingService $coaching): void
    {
        $this->validate([
            'traineeId' => ['required', 'integer', 'exists:users,id'],
            'reviewType' => ['required', 'string'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $trainee = User::query()->findOrFail($this->traineeId);

        $coaching->submitReview(auth()->user(), $trainee, [
            'review_type' => $this->reviewType,
            'score' => $this->score,
            'feedback' => $this->feedback !== '' ? $this->feedback : null,
        ]);

        session()->flash('coaching_status', 'review-submitted');
        $this->reset(['score', 'feedback']);
    }

    public function signOffFap(int $traineeId, TrainingCoachingService $coaching): void
    {
        $trainee = User::query()->findOrFail($traineeId);

        $coaching->signOffFap(
            auth()->user(),
            $trainee,
            $this->feedback !== '' ? $this->feedback : null,
            $this->score,
        );

        session()->flash('coaching_status', 'fap-signed-off');
        $this->reset(['score', 'feedback', 'traineeId']);
    }

    public function registerSession(int $sessionId, TrainingCoachingService $coaching): void
    {
        $session = \App\Models\TrainingSession::query()->findOrFail($sessionId);
        $coaching->registerForSession(auth()->user(), $session);
        session()->flash('coaching_status', 'session-registered');
    }

    public function createSession(TrainingCoachingService $coaching): void
    {
        $this->validate([
            'sessionTitle' => ['required', 'string', 'max:255'],
            'sessionDescription' => ['nullable', 'string', 'max:5000'],
            'sessionType' => ['required', 'string'],
            'sessionStartsAt' => ['required', 'date'],
            'sessionCapacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $coaching->createSession(auth()->user(), [
            'title' => $this->sessionTitle,
            'description' => $this->sessionDescription !== '' ? $this->sessionDescription : null,
            'session_type' => $this->sessionType,
            'starts_at' => Carbon::parse($this->sessionStartsAt),
            'capacity' => $this->sessionCapacity,
        ]);

        session()->flash('coaching_status', 'session-created');
        $this->reset(['sessionTitle', 'sessionDescription', 'sessionCapacity']);
        $this->sessionType = 'live';
        $this->sessionStartsAt = now()->addWeek()->format('Y-m-d\TH:i');
    }

    public function render(TrainingCoachingService $coaching): View
    {
        $hub = $coaching->hubFor(auth()->user());

        return view('livewire.training.coaching-center', [
            'hub' => $hub,
            'mentorTrainees' => $hub['is_mentor']
                ? $coaching->mentorTrainees(auth()->user())
                : collect(),
        ]);
    }
}
