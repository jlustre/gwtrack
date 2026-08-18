<?php

namespace App\Services\Prospects;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ProspectAppointment;
use App\Models\ProspectCommunication;
use App\Models\ProspectFollowUp;
use App\Models\ProspectFunnelStage;
use App\Models\User;
use Carbon\CarbonInterface;

class CrmAutomationService
{
    public function __construct(private readonly ProspectTaskBridge $tasks) {}

    public function handleActivityLogged(ProspectActivity $activity): void
    {
        if (! filled($activity->next_action) && ! filled($activity->next_follow_up_at)) {
            return;
        }

        $activity->loadMissing(['prospect', 'user']);

        $this->createOperationalFollowUp(
            prospect: $activity->prospect,
            actor: $activity->user,
            dueAt: $activity->next_follow_up_at,
            nextAction: $activity->next_action,
            config: config('crm-automation.activity_next_action', []),
        );
    }

    public function handleCommunicationLogged(ProspectCommunication $communication): void
    {
        if (! filled($communication->next_action) && ! filled($communication->next_follow_up_at)) {
            return;
        }

        $communication->loadMissing(['prospect', 'user']);

        $this->createOperationalFollowUp(
            prospect: $communication->prospect,
            actor: $communication->user,
            dueAt: $communication->next_follow_up_at,
            nextAction: $communication->next_action,
            config: config('crm-automation.communication_next_action', []),
        );
    }

    public function handleStageChanged(Prospect $prospect, User $actor, int $toStageId): void
    {
        $prospect->loadMissing(['stage', 'funnel']);

        $funnelStage = ProspectFunnelStage::query()
            ->with('pipelineStage')
            ->where('prospect_funnel_id', $prospect->prospect_funnel_id)
            ->where('pipeline_stage_id', $toStageId)
            ->first();

        $followUpConfig = $funnelStage?->auto_task_template['follow_up'] ?? null;

        if (! is_array($followUpConfig)) {
            $pipelineSlug = $prospect->stage?->slug ?? $funnelStage?->pipelineStage?->slug;
            $followUpConfig = $pipelineSlug ? config("prospects.stage_automations.{$pipelineSlug}") : null;
        }

        if (is_array($followUpConfig)) {
            $this->createStageFollowUp($prospect, $actor, $followUpConfig);
        }

        $taskTemplate = $funnelStage?->auto_task_template['task'] ?? null;

        if (is_array($taskTemplate) && config('crm-automation.stage_entered.create_task_from_template', true)) {
            $this->tasks->createFromStageTemplate($prospect, $actor, $taskTemplate);
        }
    }

    public function handleMissedAppointment(ProspectAppointment $appointment): void
    {
        $appointment->loadMissing(['prospect', 'owner', 'type']);

        $config = config('crm-automation.missed_appointment', []);
        $offsetDays = (int) ($config['offset_days'] ?? 1);

        $followUp = $this->firstOrCreateFollowUp(
            prospect: $appointment->prospect,
            assignedUserId: $appointment->owner_id,
            followupType: $config['followup_type'] ?? 'missed_appointment',
            dueAt: now()->addDays($offsetDays),
            priority: $config['priority'] ?? 'high',
            notes: $config['notes'] ?? 'Appointment was missed. Reschedule and confirm the next meeting time.',
        );

        if ($followUp && ! empty($config['create_task'])) {
            $this->tasks->createFromMissedAppointment($appointment);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createOperationalFollowUp(Prospect $prospect, User $actor, mixed $dueAt, ?string $nextAction, array $config): void
    {
        $dueAt = $dueAt ?: now()->addDay();
        $followUp = $this->firstOrCreateFollowUp(
            prospect: $prospect,
            assignedUserId: $prospect->owner_id,
            followupType: $config['followup_type'] ?? 'next_action',
            dueAt: $dueAt,
            priority: $config['priority'] ?? 'medium',
            notes: $nextAction,
        );

        if (! $followUp) {
            return;
        }

        if (! empty($config['create_task'])) {
            $this->tasks->createFromFollowUp(
                followUp: $followUp,
                title: ($config['title_prefix'] ?? 'Follow up').': '.$prospect->displayName(),
                description: $nextAction,
            );
        }

        if (! empty($config['create_calendar_event']) && $this->hasSpecificTime($followUp->due_at)) {
            $this->createCalendarFollowUpEvent($followUp, $actor, $config);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createStageFollowUp(Prospect $prospect, User $actor, array $config): void
    {
        $offsetDays = (int) ($config['offset_days'] ?? 1);
        $followUp = $this->firstOrCreateFollowUp(
            prospect: $prospect,
            assignedUserId: $prospect->owner_id,
            followupType: $config['followup_type'] ?? 'stage_automation',
            dueAt: now()->addDays($offsetDays),
            priority: $config['priority'] ?? 'medium',
            notes: $config['notes'] ?? null,
        );

        if (! $followUp) {
            return;
        }

        if (! empty($config['create_task'])) {
            $this->tasks->createFromFollowUp(
                followUp: $followUp,
                title: $config['task_title'] ?? 'Follow up: '.$prospect->displayName(),
                description: $config['notes'] ?? null,
            );
        }

        if (
            config('crm-automation.stage_entered.create_calendar_event_when_timed', true)
            && ! empty($config['create_calendar_event'])
            && $this->hasSpecificTime($followUp->due_at)
        ) {
            $this->createCalendarFollowUpEvent($followUp, $actor, [
                'calendar_event_type' => $config['calendar_event_type'] ?? config('crm-automation.stage_entered.calendar_event_type'),
                'calendar_duration_minutes' => $config['calendar_duration_minutes'] ?? config('crm-automation.stage_entered.calendar_duration_minutes'),
            ]);
        }
    }

    private function firstOrCreateFollowUp(
        Prospect $prospect,
        int $assignedUserId,
        string $followupType,
        mixed $dueAt,
        string $priority,
        ?string $notes,
    ): ?ProspectFollowUp {
        $existing = ProspectFollowUp::query()
            ->where('prospect_id', $prospect->id)
            ->where('assigned_user_id', $assignedUserId)
            ->where('followup_type', $followupType)
            ->whereIn('status', ['pending', 'overdue'])
            ->first();

        if ($existing) {
            return null;
        }

        return ProspectFollowUp::create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $assignedUserId,
            'due_at' => $dueAt,
            'followup_type' => $followupType,
            'priority' => $priority,
            'status' => 'pending',
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createCalendarFollowUpEvent(ProspectFollowUp $followUp, User $actor, array $config): CalendarEvent
    {
        $followUp->loadMissing(['prospect', 'assignedUser']);

        $type = CalendarEventType::query()
            ->where('slug', $config['calendar_event_type'] ?? 'crm-follow-up')
            ->where('is_active', true)
            ->first();
        $category = CalendarCategory::query()->where('slug', 'prospects')->first();
        $startsAt = $followUp->due_at;
        $duration = (int) ($config['calendar_duration_minutes'] ?? 30);

        $event = CalendarEvent::create([
            'calendar_event_type_id' => $type?->id,
            'calendar_category_id' => $category?->id ?? $type?->calendar_category_id,
            'organizer_id' => $followUp->assigned_user_id,
            'title' => 'CRM Follow-Up: '.$followUp->prospect->displayName(),
            'description' => $followUp->notes,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt?->copy()->addMinutes($duration),
            'timezone' => $followUp->prospect->timezone ?? config('app.timezone', 'America/Vancouver'),
            'visibility' => 'private',
            'status' => 'scheduled',
            'related_prospect_id' => $followUp->prospect_id,
            'related_contact_id' => $followUp->prospect?->contact_id,
            'related_sales_opportunity_id' => $followUp->prospect?->sales_opportunity_id,
            'related_recruiting_opportunity_id' => $followUp->prospect?->recruiting_opportunity_id,
            'notes' => 'CRM source: Prospect follow-up',
        ]);

        CalendarEventAttendee::create([
            'calendar_event_id' => $event->id,
            'prospect_id' => $followUp->prospect_id,
            'name' => $followUp->prospect->displayName(),
            'email' => $followUp->prospect->email,
            'attendee_type' => 'prospect',
            'rsvp_status' => 'pending',
        ]);

        if ((int) $actor->id !== (int) $followUp->assigned_user_id) {
            CalendarEventAttendee::create([
                'calendar_event_id' => $event->id,
                'user_id' => $actor->id,
                'name' => $actor->name,
                'email' => $actor->email,
                'attendee_type' => 'automation_actor',
                'rsvp_status' => 'accepted',
                'responded_at' => now(),
            ]);
        }

        return $event;
    }

    private function hasSpecificTime(?CarbonInterface $date): bool
    {
        return $date !== null && $date->format('H:i:s') !== '00:00:00';
    }
}
