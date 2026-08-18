<?php

namespace App\Livewire\Dashboard;

use App\Models\ScheduledPhoneCall;
use App\Services\ScheduledPhoneCallService;
use App\Services\TaskAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class DashboardSchedulePhoneCallModal extends Component
{
    private const SEARCH_MIN_LENGTH = 3;

    public bool $show = false;

    public string $step = 'list';

    public string $search = '';

    public ?string $selectedPersonKey = null;

    public string $selectedPersonLabel = '';

    public string $selectedPersonSubtitle = '';

    /** @var array<string, mixed> */
    public array $selectedPerson = [];

    public bool $showScheduleForm = false;

    public int $scheduleFormKey = 0;

    public ?int $activeCallId = null;

    public ?int $editingCallId = null;

    public string $rescheduleAt = '';

    public string $rescheduleNotes = '';

    public string $result = '';

    public string $resultNotes = '';

    public string $callbackAt = '';

    public ?string $statusMessage = null;

    #[On('open-dashboard-schedule-phone-call-modal')]
    public function open(): void
    {
        $this->resetFlow();
        $this->step = 'list';
        $this->show = true;
    }

    #[On('viewCallFromPanel')]
    public function viewCallFromPanel(int $callId): void
    {
        $this->resetFlow();
        $this->show = true;
        $this->viewCall($callId);
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetFlow();
    }

    public function startScheduleNew(): void
    {
        $this->activeCallId = null;
        $this->editingCallId = null;
        $this->selectedPersonKey = null;
        $this->selectedPersonLabel = '';
        $this->selectedPersonSubtitle = '';
        $this->selectedPerson = [];
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;
        $this->search = '';
        $this->step = 'search';
        $this->statusMessage = null;
        $this->resetValidation();
    }

    public function backToList(): void
    {
        $this->activeCallId = null;
        $this->editingCallId = null;
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;
        $this->selectedPerson = [];
        $this->selectedPersonKey = null;
        $this->selectedPersonLabel = '';
        $this->selectedPersonSubtitle = '';
        $this->search = '';
        $this->step = 'list';
        $this->resetValidation();
    }

    public function backToSearch(): void
    {
        $this->selectedPersonKey = null;
        $this->selectedPersonLabel = '';
        $this->selectedPersonSubtitle = '';
        $this->selectedPerson = [];
        $this->editingCallId = null;
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;
        $this->step = 'search';
        $this->resetValidation();
    }

    public function selectPerson(string $key, TaskAssignmentService $assignments): void
    {
        $viewer = auth()->user();
        abort_unless($viewer, 403);

        $person = $assignments->resolvePersonForPhoneCall($viewer, $key);

        if (! $person) {
            $this->addError('search', 'Unable to schedule a call for this person.');

            return;
        }

        $this->selectedPersonKey = $key;
        $this->selectedPersonLabel = (string) $person['contact_name'];
        $this->selectedPersonSubtitle = match ($person['person_type']) {
            'client' => 'Client',
            'prospect' => 'Prospect',
            'team_member' => 'Team Member',
            default => 'Other Person',
        };
        $this->selectedPerson = $person;
        $this->editingCallId = null;
        $this->step = 'detail';
        $this->showScheduleForm = true;
        $this->scheduleFormKey++;
        $this->resetValidation();
    }

    public function openExternalForm(): void
    {
        $this->step = 'external';
        $this->selectedPerson = [
            'person_type' => 'external',
            'prospect_id' => null,
            'related_user_id' => null,
            'contact_name' => '',
            'contact_phone' => '',
            'contact_email' => '',
        ];
        $this->showScheduleForm = true;
        $this->editingCallId = null;
        $this->scheduleFormKey++;
    }

    public function openScheduleForm(): void
    {
        $this->showScheduleForm = true;
        $this->editingCallId = null;
        $this->scheduleFormKey++;
    }

    public function viewCall(int $callId): void
    {
        $this->authorizeCall($callId);
        $this->activeCallId = $callId;
        $this->result = '';
        $this->resultNotes = '';
        $this->callbackAt = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->step = 'view';
        $this->resetValidation();
    }

    public function editCall(int $callId): void
    {
        $call = $this->authorizeCall($callId);
        $this->editingCallId = $callId;
        $this->activeCallId = $callId;
        $this->selectedPerson = $this->personFromCall($call);
        $this->step = 'edit';
        $this->showScheduleForm = true;
        $this->scheduleFormKey++;
    }

    public function startReschedule(int $callId): void
    {
        $call = $this->authorizeCall($callId);
        $this->activeCallId = $callId;
        $this->rescheduleAt = $call->scheduled_at->format('Y-m-d\TH:i');
        $this->rescheduleNotes = (string) ($call->notes ?? '');
        $this->step = 'reschedule';
    }

    public function saveReschedule(ScheduledPhoneCallService $phoneCalls): void
    {
        abort_unless($this->activeCallId, 403);

        $validated = $this->validate([
            'rescheduleAt' => ['required', 'date', 'after:now'],
            'rescheduleNotes' => ['nullable', 'string', 'max:5000'],
        ], [
            'rescheduleAt.required' => 'Choose a new date and time for this call.',
            'rescheduleAt.after' => 'The rescheduled call must be in the future.',
        ]);

        $call = ScheduledPhoneCall::query()
            ->whereKey($this->activeCallId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $phoneCalls->reschedule(
            auth()->user(),
            $call,
            Carbon::parse($validated['rescheduleAt']),
            filled($validated['rescheduleNotes'] ?? null) ? $validated['rescheduleNotes'] : null,
        );

        $this->statusMessage = 'Phone call rescheduled.';
        $this->dispatch('scheduled-phone-call-changed');
        $this->backToList();
    }

    public function deleteCall(int $callId, ScheduledPhoneCallService $phoneCalls): void
    {
        $call = $this->authorizeCall($callId);
        $phoneCalls->delete(auth()->user(), $call);

        if ($this->activeCallId === $callId) {
            $this->activeCallId = null;
        }

        $this->editingCallId = null;
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;

        $this->statusMessage = 'Phone call deleted.';
        $this->dispatch('scheduled-phone-call-changed');
        $this->backToList();
    }

    public function saveCallResult(ScheduledPhoneCallService $phoneCalls): void
    {
        abort_unless($this->activeCallId && $this->step === 'view', 403);

        $resultKeys = implode(',', array_keys(config('scheduled-phone-calls.results', [])));

        $rules = [
            'result' => ['required', 'in:'.$resultKeys],
            'resultNotes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->result === 'callback') {
            $rules['callbackAt'] = ['required', 'date', 'after:now'];
        }

        $validated = $this->validate($rules, [
            'result.required' => 'Select a call result.',
            'callbackAt.required' => 'Choose when to schedule the callback.',
            'callbackAt.after' => 'The callback must be scheduled in the future.',
        ]);

        $call = ScheduledPhoneCall::query()
            ->whereKey($this->activeCallId)
            ->where('user_id', auth()->id())
            ->where('status', 'scheduled')
            ->firstOrFail();

        $phoneCalls->recordResult(auth()->user(), $call, [
            'result' => $validated['result'],
            'result_notes' => $validated['resultNotes'] ?? null,
            'callback_at' => $validated['result'] === 'callback'
                ? Carbon::parse($validated['callbackAt'])
                : null,
        ]);

        $this->statusMessage = match ($validated['result']) {
            'completed' => 'Call marked completed and removed from your list.',
            'callback' => 'Callback scheduled. The call is back on your active list.',
            default => 'Result saved. This call stays on your list until completed or deleted.',
        };
        $this->dispatch('scheduled-phone-call-changed');
        $this->backToList();
    }

    #[On('scheduled-phone-call-saved')]
    public function onScheduled(): void
    {
        $this->statusMessage = $this->editingCallId
            ? 'Phone call updated.'
            : 'Phone call scheduled. It will appear in Upcoming Phone Calls and your calendar.';
        $this->showScheduleForm = false;
        $this->editingCallId = null;
        $this->dispatch('scheduled-phone-call-changed');
        $this->backToList();
    }

    public function render(TaskAssignmentService $assignments, ScheduledPhoneCallService $phoneCalls): View
    {
        $viewer = auth()->user();
        $needle = trim($this->search);

        $personOptions = $viewer && strlen($needle) >= self::SEARCH_MIN_LENGTH
            ? $assignments->searchRelatedPeopleFor($viewer, $needle)
            : collect();

        $panels = $viewer
            ? $phoneCalls->upcomingPanelsFor($viewer, 30)
            : ['today' => collect(), 'tomorrow' => collect(), 'later' => collect(), 'follow_up' => collect(), 'stats' => ['total' => 0, 'today' => 0, 'tomorrow' => 0, 'follow_up' => 0, 'active' => 0]];

        $activeCall = $this->activeCallId
            ? ScheduledPhoneCall::query()
                ->with(['prospect', 'relatedUser', 'calendarEvent'])
                ->where('user_id', $viewer?->id)
                ->whereKey($this->activeCallId)
                ->first()
            : null;

        return view('livewire.dashboard.dashboard-schedule-phone-call-modal', [
            'personOptions' => $personOptions,
            'panels' => $panels,
            'activeCall' => $activeCall,
            'calendarUrl' => route('calendar.index'),
            'results' => config('scheduled-phone-calls.results', []),
        ]);
    }

    private function authorizeCall(int $callId): ScheduledPhoneCall
    {
        return ScheduledPhoneCall::query()
            ->whereKey($callId)
            ->where('user_id', auth()->id())
            ->where('status', 'scheduled')
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function personFromCall(ScheduledPhoneCall $call): array
    {
        return [
            'person_type' => $call->person_type,
            'prospect_id' => $call->prospect_id,
            'related_user_id' => $call->related_user_id,
            'contact_name' => $call->contact_name,
            'contact_phone' => $call->contact_phone ?? '',
            'contact_email' => $call->contact_email ?? '',
            'scheduled_at' => $call->scheduled_at->format('Y-m-d\TH:i'),
            'purpose' => $call->purpose ?? '',
            'notes' => $call->notes ?? '',
        ];
    }

    private function resetFlow(): void
    {
        $this->step = 'list';
        $this->search = '';
        $this->selectedPersonKey = null;
        $this->selectedPersonLabel = '';
        $this->selectedPersonSubtitle = '';
        $this->selectedPerson = [];
        $this->showScheduleForm = false;
        $this->scheduleFormKey = 0;
        $this->activeCallId = null;
        $this->editingCallId = null;
        $this->rescheduleAt = '';
        $this->rescheduleNotes = '';
        $this->result = '';
        $this->resultNotes = '';
        $this->callbackAt = '';
        $this->statusMessage = null;
        $this->resetValidation();
    }
}
