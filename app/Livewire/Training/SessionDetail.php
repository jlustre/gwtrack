<?php

namespace App\Livewire\Training;

use App\Models\TrainingSession;
use App\Services\Training\TrainingCalendarService;
use App\Services\Training\TrainingCoachingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SessionDetail extends Component
{
    public TrainingSession $session;

    public bool $canManage;

    public function mount(TrainingSession $session, bool $canManage): void
    {
        abort_unless($session->is_active, 404);

        $this->session = $session->load(['instructor', 'module', 'calendarEvent', 'attendance.user']);
        $this->canManage = $canManage;
    }

    public function register(TrainingCoachingService $coaching): void
    {
        $coaching->registerForSession(auth()->user(), $this->session);
        session()->flash('session_status', 'registered');
        $this->session->refresh()->load(['attendance.user', 'calendarEvent']);
    }

    public function checkIn(TrainingCalendarService $calendar): void
    {
        $calendar->selfCheckIn(auth()->user(), $this->session);
        session()->flash('session_status', 'checked-in');
        $this->session->refresh()->load(['attendance.user', 'calendarEvent']);
    }

    public function markAttended(int $attendanceId, TrainingCalendarService $calendar): void
    {
        abort_unless($this->canManage, 403);

        $attendance = $this->session->attendance()->findOrFail($attendanceId);
        $calendar->checkIn($attendance, auth()->user());
        session()->flash('session_status', 'attendance-updated');
        $this->session->refresh()->load(['attendance.user', 'calendarEvent']);
    }

    public function render(): View
    {
        $userAttendance = $this->session->attendance->firstWhere('user_id', auth()->id());

        return view('livewire.training.session-detail', [
            'userAttendance' => $userAttendance,
            'calendarUrl' => $this->session->calendar_event_id
                ? route('calendar.events.show', $this->session->calendar_event_id)
                : null,
        ]);
    }
}
