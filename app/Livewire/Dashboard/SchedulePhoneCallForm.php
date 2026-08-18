<?php

namespace App\Livewire\Dashboard;

use App\Models\ScheduledPhoneCall;
use App\Services\ScheduledPhoneCallService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class SchedulePhoneCallForm extends Component
{
    public ?int $callId = null;

    public string $personType = 'external';

    public ?string $prospectId = null;

    public ?int $relatedUserId = null;

    public string $contactName = '';

    public string $contactPhone = '';

    public string $contactEmail = '';

    public string $scheduledAt = '';

    public string $purpose = '';

    public string $notes = '';

    public bool $syncCalendar = true;

    public bool $createPrepTask = true;

    /**
     * @param  array<string, mixed>  $person
     */
    public function mount(array $person = [], ?int $callId = null): void
    {
        $this->callId = $callId;

        if ($callId !== null) {
            $this->fillFromCall($callId);
        } elseif ($person !== []) {
            $this->fillFromPerson($person);
        }

        if ($this->scheduledAt === '') {
            $this->scheduledAt = now()->addHour()->startOfHour()->format('Y-m-d\TH:i');
        }
    }

    public function save(ScheduledPhoneCallService $phoneCalls): void
    {
        $validated = $this->validate([
            'contactName' => ['required', 'string', 'max:120'],
            'contactPhone' => ['nullable', 'string', 'max:40'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'scheduledAt' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'contactName.required' => 'Enter who you are calling.',
            'scheduledAt.required' => 'Choose when to place this call.',
        ]);

        $payload = [
            'contact_name' => $validated['contactName'],
            'contact_phone' => filled($validated['contactPhone'] ?? null) ? $validated['contactPhone'] : null,
            'contact_email' => filled($validated['contactEmail'] ?? null) ? $validated['contactEmail'] : null,
            'scheduled_at' => Carbon::parse($validated['scheduledAt']),
            'purpose' => filled($validated['purpose'] ?? null) ? $validated['purpose'] : null,
            'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
            'sync_calendar' => $this->syncCalendar,
        ];

        if ($this->callId !== null) {
            $call = ScheduledPhoneCall::query()
                ->whereKey($this->callId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $phoneCalls->update(auth()->user(), $call, $payload);
        } else {
            $phoneCalls->schedule(auth()->user(), array_merge($payload, [
                'person_type' => $this->personType,
                'prospect_id' => $this->prospectId,
                'related_user_id' => $this->relatedUserId,
                'create_prep_task' => $this->createPrepTask,
            ]));
        }

        $this->dispatch('scheduled-phone-call-saved');
    }

    public function render(): View
    {
        return view('livewire.dashboard.schedule-phone-call-form', [
            'isEditing' => $this->callId !== null,
        ]);
    }

    private function fillFromCall(int $callId): void
    {
        $call = ScheduledPhoneCall::query()
            ->whereKey($callId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->personType = $call->person_type;
        $this->prospectId = $call->prospect_id;
        $this->relatedUserId = $call->related_user_id;
        $this->contactName = $call->contact_name;
        $this->contactPhone = $call->contact_phone ?? '';
        $this->contactEmail = $call->contact_email ?? '';
        $this->scheduledAt = $call->scheduled_at->format('Y-m-d\TH:i');
        $this->purpose = $call->purpose ?? '';
        $this->notes = $call->notes ?? '';
        $this->createPrepTask = false;
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private function fillFromPerson(array $person): void
    {
        $this->personType = (string) ($person['person_type'] ?? 'external');
        $this->prospectId = isset($person['prospect_id']) ? (string) $person['prospect_id'] : null;
        $this->relatedUserId = isset($person['related_user_id']) ? (int) $person['related_user_id'] : null;
        $this->contactName = (string) ($person['contact_name'] ?? '');
        $this->contactPhone = (string) ($person['contact_phone'] ?? '');
        $this->contactEmail = (string) ($person['contact_email'] ?? '');

        if (filled($person['scheduled_at'] ?? null)) {
            $this->scheduledAt = (string) $person['scheduled_at'];
        }

        if (filled($person['purpose'] ?? null)) {
            $this->purpose = (string) $person['purpose'];
        }

        if (filled($person['notes'] ?? null)) {
            $this->notes = (string) $person['notes'];
        }
    }
}
