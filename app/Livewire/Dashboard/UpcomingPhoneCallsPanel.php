<?php

namespace App\Livewire\Dashboard;

use App\Models\ScheduledPhoneCall;
use App\Services\ScheduledPhoneCallService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class UpcomingPhoneCallsPanel extends Component
{
    public bool $compact = false;

    public ?int $activeCallId = null;

    public ?int $rescheduleCallId = null;

    public string $rescheduleAt = '';

    public string $rescheduleNotes = '';

    public string $result = '';

    public string $resultNotes = '';

    public string $callbackAt = '';

    #[On('scheduled-phone-call-changed')]
    public function refreshPanel(): void
    {
        // Re-render upcoming phone calls.
    }

    public function openResultForm(int $callId): void
    {
        $this->cancelReschedule();
        $this->activeCallId = $callId;
        $this->result = '';
        $this->resultNotes = '';
        $this->callbackAt = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
    }

    public function startReschedule(int $callId): void
    {
        $call = ScheduledPhoneCall::query()
            ->whereKey($callId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->cancelResultForm();
        $this->rescheduleCallId = $callId;
        $this->rescheduleAt = $call->scheduled_at->format('Y-m-d\TH:i');
        $this->rescheduleNotes = (string) ($call->notes ?? '');
        $this->resetValidation();
    }

    public function cancelReschedule(): void
    {
        $this->rescheduleCallId = null;
        $this->rescheduleAt = '';
        $this->rescheduleNotes = '';
        $this->resetValidation();
    }

    public function saveReschedule(ScheduledPhoneCallService $phoneCalls): void
    {
        abort_unless($this->rescheduleCallId, 403);

        $validated = $this->validate([
            'rescheduleAt' => ['required', 'date', 'after:now'],
            'rescheduleNotes' => ['nullable', 'string', 'max:5000'],
        ], [
            'rescheduleAt.required' => 'Choose a new date and time for this call.',
            'rescheduleAt.after' => 'The rescheduled call must be in the future.',
        ]);

        $call = ScheduledPhoneCall::query()
            ->whereKey($this->rescheduleCallId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $phoneCalls->reschedule(
            auth()->user(),
            $call,
            Carbon::parse($validated['rescheduleAt']),
            filled($validated['rescheduleNotes'] ?? null) ? $validated['rescheduleNotes'] : null,
        );

        $this->cancelReschedule();
        $this->dispatch('scheduled-phone-call-changed');
        session()->flash('phone_calls_status', 'Phone call rescheduled.');
    }

    public function deleteCall(int $callId, ScheduledPhoneCallService $phoneCalls): void
    {
        $call = ScheduledPhoneCall::query()
            ->whereKey($callId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $phoneCalls->delete(auth()->user(), $call);

        if ($this->activeCallId === $callId) {
            $this->cancelResultForm();
        }

        if ($this->rescheduleCallId === $callId) {
            $this->cancelReschedule();
        }

        $this->dispatch('scheduled-phone-call-changed');
        session()->flash('phone_calls_status', 'Phone call deleted.');
    }

    public function cancelResultForm(): void
    {
        $this->activeCallId = null;
        $this->resetValidation();
    }

    public function saveResult(ScheduledPhoneCallService $phoneCalls): void
    {
        abort_unless($this->activeCallId, 403);

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
            ->firstOrFail();

        $phoneCalls->recordResult(auth()->user(), $call, [
            'result' => $validated['result'],
            'result_notes' => $validated['resultNotes'] ?? null,
            'callback_at' => $validated['result'] === 'callback'
                ? Carbon::parse($validated['callbackAt'])
                : null,
        ]);

        $this->activeCallId = null;
        $this->dispatch('scheduled-phone-call-changed');
        session()->flash('phone_calls_status', 'Phone call result saved.');
    }

    public function render(ScheduledPhoneCallService $phoneCalls): View
    {
        $viewer = auth()->user();
        $panels = $viewer
            ? $phoneCalls->upcomingPanelsFor($viewer, $this->compact ? 8 : 15)
            : ['today' => collect(), 'tomorrow' => collect(), 'later' => collect(), 'follow_up' => collect(), 'stats' => ['total' => 0, 'today' => 0, 'tomorrow' => 0, 'follow_up' => 0, 'active' => 0]];

        return view('livewire.dashboard.upcoming-phone-calls-panel', [
            'panels' => $panels,
            'results' => config('scheduled-phone-calls.results', []),
        ]);
    }
}
