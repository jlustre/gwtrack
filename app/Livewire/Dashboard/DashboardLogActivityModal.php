<?php

namespace App\Livewire\Dashboard;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ProspectAppointment;
use App\Services\TaskAssignmentService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class DashboardLogActivityModal extends Component
{
    private const SEARCH_MIN_LENGTH = 3;

    public bool $show = false;

    public string $step = 'search';

    public string $viewTab = 'log';

    public string $search = '';

    public ?string $selectedPersonKey = null;

    public string $selectedPersonLabel = '';

    public string $selectedPersonSubtitle = '';

    public ?string $prospectId = null;

    public bool $showLogForm = false;

    public int $activityFormKey = 0;

    public bool $showScheduleForm = false;

    public int $scheduleFormKey = 0;

    public ?int $editingActivityId = null;

    public ?int $editingAppointmentId = null;

    public ?int $viewingActivityId = null;

    public ?int $viewingAppointmentId = null;

    public ?string $statusMessage = null;

    #[On('open-dashboard-prospect-activity-modal')]
    public function openProspectActivity(?string $tab = null): void
    {
        $this->openWithTab($tab ?? 'log');
    }

    #[On('open-dashboard-log-activity-modal')]
    #[On('open-prospect-log-activity-picker')]
    public function open(): void
    {
        $this->openWithTab('log');
    }

    #[On('open-dashboard-schedule-activity-modal')]
    public function openSchedule(): void
    {
        $this->openWithTab('schedule');
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetPersonSelection();
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['log', 'schedule'], true)) {
            return;
        }

        $this->viewTab = $tab;
        $this->clearItemPanels();
        $this->statusMessage = null;
    }

    public function selectPerson(string $key, string $label, string $subtitle, TaskAssignmentService $assignments): void
    {
        $viewer = auth()->user();

        abort_unless($viewer, 403);

        $this->selectedPersonKey = $key;
        $this->selectedPersonLabel = $label;
        $this->selectedPersonSubtitle = $subtitle;

        $prospect = $assignments->resolveProspectForPersonKey($viewer, $key);

        if (! $prospect) {
            $this->step = 'unresolved';
            $this->prospectId = null;

            return;
        }

        if (! $viewer->can('update', $prospect)) {
            $this->addError('search', 'You do not have permission to manage activity for this person.');

            return;
        }

        $this->prospectId = (string) $prospect->id;
        $this->step = 'detail';
        $this->clearItemPanels();
        $this->resetValidation();
    }

    public function backToSearch(): void
    {
        $this->resetPersonSelection();
    }

    public function openAddActivityForm(): void
    {
        if (! $this->prospectId) {
            return;
        }

        $this->viewTab = 'log';
        $this->editingActivityId = null;
        $this->viewingActivityId = null;
        $this->showLogForm = true;
        $this->showScheduleForm = false;
        $this->activityFormKey++;
    }

    public function viewActivity(int $activityId): void
    {
        $this->authorizeActivity($activityId);

        $this->viewTab = 'log';
        $this->viewingActivityId = $activityId;
        $this->editingActivityId = null;
        $this->showLogForm = false;
        $this->showScheduleForm = false;
    }

    public function editActivity(int $activityId): void
    {
        $this->authorizeActivity($activityId);

        $this->viewTab = 'log';
        $this->editingActivityId = $activityId;
        $this->viewingActivityId = null;
        $this->showLogForm = true;
        $this->showScheduleForm = false;
        $this->activityFormKey++;
    }

    public function closeActivityPanel(): void
    {
        $this->viewingActivityId = null;
        $this->editingActivityId = null;
        $this->showLogForm = false;
    }

    public function openScheduleForm(): void
    {
        if (! $this->prospectId) {
            return;
        }

        $this->viewTab = 'schedule';
        $this->editingAppointmentId = null;
        $this->viewingAppointmentId = null;
        $this->showScheduleForm = true;
        $this->showLogForm = false;
        $this->scheduleFormKey++;
    }

    public function viewAppointment(int $appointmentId): void
    {
        $this->authorizeAppointment($appointmentId);

        $this->viewTab = 'schedule';
        $this->viewingAppointmentId = $appointmentId;
        $this->editingAppointmentId = null;
        $this->showLogForm = false;
        $this->showScheduleForm = false;
    }

    public function editAppointment(int $appointmentId): void
    {
        $this->authorizeAppointment($appointmentId);

        $this->viewTab = 'schedule';
        $this->editingAppointmentId = $appointmentId;
        $this->viewingAppointmentId = null;
        $this->showScheduleForm = true;
        $this->showLogForm = false;
        $this->scheduleFormKey++;
    }

    public function closeAppointmentPanel(): void
    {
        $this->viewingAppointmentId = null;
        $this->editingAppointmentId = null;
        $this->showScheduleForm = false;
    }

    #[On('prospect-activity-logged')]
    public function onActivityLogged(): void
    {
        $this->statusMessage = $this->editingActivityId
            ? 'Activity updated successfully.'
            : 'Activity logged successfully.';
        $this->closeActivityPanel();
    }

    #[On('cancel-prospect-activity-log-form')]
    public function closeLogForm(): void
    {
        $this->closeActivityPanel();
    }

    #[On('prospect-scheduled-activity-saved')]
    public function onScheduled(): void
    {
        $this->statusMessage = $this->editingAppointmentId
            ? 'Scheduled activity updated successfully.'
            : 'Activity scheduled successfully. Calendar and tasks were updated.';
        $this->closeAppointmentPanel();
    }

    #[On('cancel-prospect-schedule-activity-form')]
    public function closeScheduleForm(): void
    {
        $this->closeAppointmentPanel();
    }

    public function openFullLogTools(): void
    {
        if (! $this->prospectId) {
            return;
        }

        $this->dispatch(
            'open-prospect-quick-log-modal',
            prospectId: $this->prospectId,
            tab: 'activity',
            activityType: 'phone_call',
        );

        $this->close();
    }

    public function render(TaskAssignmentService $assignments): View
    {
        $viewer = auth()->user();
        $needle = trim($this->search);

        $personOptions = $viewer && strlen($needle) >= self::SEARCH_MIN_LENGTH
            ? $assignments->searchRelatedPeopleFor($viewer, $needle)
            : collect();

        $activities = collect();
        $upcomingAppointments = collect();
        $prospect = null;

        if ($this->prospectId) {
            $prospect = Prospect::query()->with('stage:id,name')->find($this->prospectId);

            if ($prospect) {
                $activities = $prospect->activities()
                    ->with('user:id,name')
                    ->orderByDesc('occurred_at')
                    ->limit(20)
                    ->get();

                $upcomingAppointments = ProspectAppointment::query()
                    ->with(['type:id,name', 'calendarEvent:id,title'])
                    ->where('prospect_id', $prospect->id)
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '>=', now()->subHours(6))
                    ->orderBy('scheduled_at')
                    ->limit(15)
                    ->get();
            }
        }

        return view('livewire.dashboard.dashboard-log-activity-modal', [
            'personOptions' => $personOptions,
            'activities' => $activities,
            'upcomingAppointments' => $upcomingAppointments,
            'prospect' => $prospect,
            'activityLogsUrl' => route('team.prospects.screen', 'activity-logs'),
            'appointmentsUrl' => route('team.prospects.appointments'),
            'calendarUrl' => route('calendar.index'),
        ]);
    }

    private function openWithTab(string $tab): void
    {
        $this->authorize('viewAny', Prospect::class);

        $this->resetPersonSelection();
        $this->viewTab = in_array($tab, ['log', 'schedule'], true) ? $tab : 'log';
        $this->show = true;
    }

    private function resetPersonSelection(): void
    {
        $this->step = 'search';
        $this->search = '';
        $this->selectedPersonKey = null;
        $this->selectedPersonLabel = '';
        $this->selectedPersonSubtitle = '';
        $this->prospectId = null;
        $this->clearItemPanels();
        $this->statusMessage = null;
        $this->resetValidation();
    }

    private function clearItemPanels(): void
    {
        $this->showLogForm = false;
        $this->activityFormKey = 0;
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;
        $this->editingActivityId = null;
        $this->editingAppointmentId = null;
        $this->viewingActivityId = null;
        $this->viewingAppointmentId = null;
    }

    private function authorizeActivity(int $activityId): void
    {
        $activity = ProspectActivity::query()
            ->whereKey($activityId)
            ->where('prospect_id', $this->prospectId)
            ->firstOrFail();

        $this->authorize('update', $activity);
    }

    private function authorizeAppointment(int $appointmentId): void
    {
        $appointment = ProspectAppointment::query()
            ->whereKey($appointmentId)
            ->where('prospect_id', $this->prospectId)
            ->firstOrFail();

        $this->authorize('update', $appointment);
    }
}
