<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Services\Training\TrainingCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function __construct(
        private readonly TrainingCalendarService $calendar,
    ) {}

    public function index(Request $request): View
    {
        return view('training.sessions.index', [
            'rows' => $this->calendar->upcomingSessionRowsFor($request->user()),
        ]);
    }

    public function show(Request $request, TrainingSession $session): View
    {
        abort_unless($session->is_active, 404);

        $session->load(['instructor', 'module', 'calendarEvent', 'attendance.user']);

        $userAttendance = $session->attendance->firstWhere('user_id', $request->user()->id);
        $canManage = $this->calendar->canManageAttendance($request->user(), $session);

        return view('training.sessions.show', [
            'session' => $session,
            'userAttendance' => $userAttendance,
            'canManage' => $canManage,
            'calendarUrl' => $session->calendar_event_id
                ? route('calendar.events.show', $session->calendar_event_id)
                : null,
        ]);
    }
}
