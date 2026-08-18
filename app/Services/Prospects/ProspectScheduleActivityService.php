<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\User;

class ProspectScheduleActivityService
{
    public function __construct(
        private readonly ProspectCalendarBridge $calendarBridge,
        private readonly ProspectTaskBridge $taskBridge,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function schedule(Prospect $prospect, User $actor, array $payload): ProspectAppointment
    {
        $appointment = ProspectAppointment::create([
            'prospect_id' => $prospect->id,
            'owner_id' => $actor->id,
            'appointment_type_id' => $payload['appointment_type_id'] ?? null,
            'scheduled_at' => $payload['scheduled_at'],
            'timezone' => config('app.timezone', 'America/Vancouver'),
            'location_or_link' => $payload['location_or_link'] ?? null,
            'purpose' => $payload['purpose'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'assigned_helper_id' => $payload['assigned_helper_id'] ?? null,
            'status' => 'scheduled',
        ]);

        if ($payload['sync_calendar'] ?? true) {
            $this->calendarBridge->pushAppointment(
                $appointment->fresh(['prospect', 'type', 'owner', 'assignedHelper']),
                durationMinutes: (int) ($payload['duration_minutes'] ?? 60),
            );
        }

        $prospect->update([
            'appointment_at' => $appointment->scheduled_at,
            'next_follow_up_at' => $appointment->scheduled_at,
        ]);

        if ($payload['create_prep_task'] ?? true) {
            $this->createPrepTask($prospect, $appointment, $payload);
        }

        return $appointment->fresh(['type', 'calendarEvent']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Prospect $prospect, User $actor, ProspectAppointment $appointment, array $payload): ProspectAppointment
    {
        abort_unless($appointment->prospect_id === $prospect->id, 404);

        $appointment->update([
            'appointment_type_id' => $payload['appointment_type_id'] ?? null,
            'scheduled_at' => $payload['scheduled_at'],
            'location_or_link' => $payload['location_or_link'] ?? null,
            'purpose' => $payload['purpose'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'assigned_helper_id' => $payload['assigned_helper_id'] ?? null,
            'status' => 'scheduled',
        ]);

        if ($payload['sync_calendar'] ?? true) {
            $this->calendarBridge->pushAppointment(
                $appointment->fresh(['prospect', 'type', 'owner', 'assignedHelper']),
                durationMinutes: (int) ($payload['duration_minutes'] ?? 60),
            );
        }

        $prospect->update([
            'appointment_at' => $appointment->scheduled_at,
            'next_follow_up_at' => $appointment->scheduled_at,
        ]);

        return $appointment->fresh(['type', 'calendarEvent']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createPrepTask(Prospect $prospect, ProspectAppointment $appointment, array $payload): void
    {
        $titleTemplate = (string) config('prospect-scheduled-activity-form.sync.prep_task_title', 'Prepare for scheduled touchpoint: :prospect');
        $title = str_replace(':prospect', $prospect->displayName(), $titleTemplate);
        $typeName = $appointment->type?->name;
        $description = collect([
            $typeName ? "Type: {$typeName}" : null,
            filled($payload['purpose'] ?? null) ? 'Purpose: '.$payload['purpose'] : null,
            filled($payload['notes'] ?? null) ? $payload['notes'] : null,
            'Scheduled: '.$appointment->scheduled_at->format('M j, Y g:i A'),
        ])->filter()->implode("\n");

        $offsetDays = (int) config('prospect-scheduled-activity-form.sync.prep_task_offset_days', 0);
        $dueDate = $appointment->scheduled_at->copy()->subDays(max(0, $offsetDays))->toDateString();

        if ($dueDate < now()->toDateString()) {
            $dueDate = now()->toDateString();
        }

        $this->taskBridge->createFromScheduledAppointment($prospect, $title, $description, $dueDate);
    }
}
