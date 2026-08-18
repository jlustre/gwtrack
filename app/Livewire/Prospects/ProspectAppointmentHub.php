<?php

namespace App\Livewire\Prospects;

use App\Models\AppointmentType;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\User;
use App\Services\Prospects\ProspectCalendarBridge;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProspectAppointmentHub extends Component
{
    public bool $showForm = false;

    public ?int $editingAppointmentId = null;

    public ?string $prospectId = null;

    public ?int $appointmentTypeId = null;

    public string $scheduledAt = '';

    public ?int $assignedHelperId = null;

    public ?string $locationOrLink = null;

    public ?string $purpose = null;

    public ?string $notes = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Prospect::class);
    }

    public function openCreateForm(?string $prospectId = null): void
    {
        $this->editingAppointmentId = null;
        $this->prospectId = $prospectId;
        $this->appointmentTypeId = null;
        $this->scheduledAt = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->assignedHelperId = null;
        $this->locationOrLink = null;
        $this->purpose = null;
        $this->notes = null;
        $this->showForm = true;
    }

    public function openEditForm(int $appointmentId): void
    {
        $appointment = ProspectAppointment::query()->with('prospect')->findOrFail($appointmentId);
        $this->authorize('update', $appointment);

        $this->editingAppointmentId = $appointment->id;
        $this->prospectId = $appointment->prospect_id;
        $this->appointmentTypeId = $appointment->appointment_type_id;
        $this->scheduledAt = $appointment->scheduled_at->format('Y-m-d\TH:i');
        $this->assignedHelperId = $appointment->assigned_helper_id;
        $this->locationOrLink = $appointment->location_or_link;
        $this->purpose = $appointment->purpose;
        $this->notes = $appointment->notes;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function save(ProspectCalendarBridge $calendarBridge): void
    {
        if ($this->appointmentTypeId === '') {
            $this->appointmentTypeId = null;
        }

        if ($this->assignedHelperId === '') {
            $this->assignedHelperId = null;
        }

        $validated = $this->validate([
            'prospectId' => ['required', 'exists:prospects,id'],
            'appointmentTypeId' => ['nullable', 'exists:appointment_types,id'],
            'scheduledAt' => ['required', 'date'],
            'assignedHelperId' => ['nullable', 'exists:users,id'],
            'locationOrLink' => ['nullable', 'string', 'max:500'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $prospect = Prospect::query()->findOrFail($validated['prospectId']);
        $this->authorize('update', $prospect);

        if ($this->editingAppointmentId) {
            $appointment = ProspectAppointment::query()->findOrFail($this->editingAppointmentId);
            $this->authorize('update', $appointment);
        } else {
            $appointment = new ProspectAppointment([
                'prospect_id' => $prospect->id,
                'owner_id' => auth()->id(),
                'status' => 'scheduled',
            ]);
        }

        $appointment->fill([
            'prospect_id' => $prospect->id,
            'owner_id' => auth()->id(),
            'appointment_type_id' => $validated['appointmentTypeId'],
            'scheduled_at' => $validated['scheduledAt'],
            'assigned_helper_id' => $validated['assignedHelperId'],
            'location_or_link' => $validated['locationOrLink'],
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'],
            'status' => 'scheduled',
        ])->save();

        $calendarBridge->pushAppointment($appointment->fresh(['prospect', 'type', 'owner', 'assignedHelper']));

        $this->closeForm();
    }

    public function cancelAppointment(int $appointmentId, ProspectCalendarBridge $calendarBridge): void
    {
        $appointment = ProspectAppointment::query()->with('prospect')->findOrFail($appointmentId);
        $this->authorize('update', $appointment);

        $appointment->update(['status' => 'cancelled']);
        $calendarBridge->cancelAppointment($appointment);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingAppointmentId = null;
        $this->reset([
            'prospectId',
            'appointmentTypeId',
            'scheduledAt',
            'assignedHelperId',
            'locationOrLink',
            'purpose',
            'notes',
        ]);
    }

    public function render(): View
    {
        $userId = auth()->id();

        $upcoming = ProspectAppointment::query()
            ->with(['prospect', 'type', 'assignedHelper'])
            ->where('owner_id', $userId)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now()->subDay())
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        $recent = ProspectAppointment::query()
            ->with(['prospect', 'type'])
            ->where('owner_id', $userId)
            ->whereIn('status', ['completed', 'cancelled', 'no_show'])
            ->orderByDesc('scheduled_at')
            ->limit(10)
            ->get();

        return view('livewire.prospects.prospect-appointment-hub', [
            'upcomingAppointments' => $upcoming,
            'recentAppointments' => $recent,
            'prospects' => $this->ownedProspects(),
            'appointmentTypes' => AppointmentType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'helpers' => User::query()->orderBy('name')->limit(50)->get(),
        ]);
    }

    private function ownedProspects(): Collection
    {
        return Prospect::query()
            ->where('owner_id', auth()->id())
            ->where('status', 'active')
            ->where('is_archived', false)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'preferred_name']);
    }
}
