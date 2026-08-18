<?php

namespace App\Livewire\Activities;

use App\Services\Activities\DailyActivityService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class DailyActivitiesPage extends Component
{
    #[Url(except: DailyActivityService::PERIOD_DAILY)]
    public string $period = DailyActivityService::PERIOD_DAILY;

    public ?string $statusMessage = null;

    public function mount(DailyActivityService $activities): void
    {
        abort_unless(auth()->check(), 403);

        if (! in_array($this->period, $activities->allowedPeriods(), true)) {
            $this->period = DailyActivityService::PERIOD_DAILY;
        }
    }

    public function updatedPeriod(DailyActivityService $activities): void
    {
        if (! in_array($this->period, $activities->allowedPeriods(), true)) {
            $this->period = DailyActivityService::PERIOD_DAILY;
        }
    }

    public function increment(string $key, DailyActivityService $activities): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $activities->increment($user, $key);
        $this->statusMessage = 'Activity updated for today.';
    }

    public function decrement(string $key, DailyActivityService $activities): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $activities->decrement($user, $key);
        $this->statusMessage = 'Activity updated for today.';
    }

    public function render(DailyActivityService $activities): View
    {
        $dashboard = $activities->dashboardFor(auth()->user(), $this->period);

        return view('livewire.activities.daily-activities-page', [
            'dashboard' => $dashboard,
            'periods' => [
                DailyActivityService::PERIOD_DAILY => 'Today',
                DailyActivityService::PERIOD_WEEKLY => 'This week',
                DailyActivityService::PERIOD_MONTHLY => 'This month',
            ],
        ])->layout('layouts.app');
    }
}
