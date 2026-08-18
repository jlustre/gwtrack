<?php

namespace App\Services\Prospects;

use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\TaskUser;
use Illuminate\Support\Collection;

class ProspectTimelineService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forProspect(Prospect $prospect): array
    {
        $items = collect()
            ->merge($this->stageEvents($prospect))
            ->merge($this->activityEvents($prospect))
            ->merge($this->communicationEvents($prospect))
            ->merge($this->noteEvents($prospect))
            ->merge($this->appointmentEvents($prospect))
            ->merge($this->calendarEvents($prospect))
            ->merge($this->followUpEvents($prospect))
            ->merge($this->taskEvents($prospect))
            ->merge($this->fnaEvents($prospect))
            ->merge($this->conversionEvents($prospect))
            ->merge($this->fileEvents($prospect));

        return $items
            ->filter(fn (array $item): bool => filled($item['occurred_at_raw'] ?? null))
            ->sortByDesc('occurred_at_raw')
            ->values()
            ->map(fn (array $item): array => [
                ...$item,
                'occurred_at' => $item['occurred_at_raw']->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ])
            ->all();
    }

    public function statsFor(Prospect $prospect): array
    {
        $upcomingCalendar = $prospect->calendarEvents()
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $nextFollowUp = $prospect->followups()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_at')
            ->first();

        $openTasks = TaskUser::query()
            ->where('related_prospect_id', $prospect->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return [
            'next_calendar_label' => $upcomingCalendar?->starts_at?->format('M j, g:i A') ?? 'No meeting scheduled',
            'next_calendar_title' => $upcomingCalendar?->title,
            'next_follow_up_label' => $nextFollowUp?->due_at?->format('M j, g:i A') ?? 'No follow-up queued',
            'next_follow_up_type' => $nextFollowUp?->followup_type,
            'open_tasks' => $openTasks,
            'fna_status' => $prospect->fnaRecords()->latest()->value('status'),
            'files_count' => $prospect->files()->count(),
        ];
    }

    private function stageEvents(Prospect $prospect): Collection
    {
        return $prospect->stageHistory()
            ->with(['changedBy:id,name', 'fromStage:id,name', 'toStage:id,name'])
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn ($history): array => $this->item(
                type: 'stage',
                label: 'Stage change',
                body: ($history->fromStage?->name ?? 'None').' -> '.($history->toStage?->name ?? 'None'),
                actor: $history->changedBy?->name ?? 'System',
                occurredAt: $history->created_at,
                meta: str($history->change_source ?? 'manual')->headline()->toString(),
            ));
    }

    private function activityEvents(Prospect $prospect): Collection
    {
        return $prospect->activities()
            ->with('user:id,name')
            ->latest('occurred_at')
            ->limit(80)
            ->get()
            ->map(fn ($activity): array => $this->item(
                type: 'activity',
                label: config('prospects.activity_types.'.$activity->activity_type, str($activity->activity_type)->replace('_', ' ')->title()->toString()),
                body: trim(($activity->outcome ? $activity->outcome.' - ' : '').($activity->notes ?? '')),
                actor: $activity->user?->name ?? 'Unknown',
                occurredAt: $activity->occurred_at,
                meta: $activity->next_follow_up_at ? 'Next follow-up '.$activity->next_follow_up_at->format('M j, g:i A') : null,
            ));
    }

    private function communicationEvents(Prospect $prospect): Collection
    {
        return $prospect->communications()
            ->with(['user:id,name', 'type:id,name'])
            ->latest('contacted_at')
            ->limit(80)
            ->get()
            ->map(fn ($communication): array => $this->item(
                type: 'communication',
                label: ($communication->type?->name ?? 'Communication').' - '.str($communication->direction)->title(),
                body: trim(($communication->outcome ? $communication->outcome.' - ' : '').($communication->notes ?? '')),
                actor: $communication->user?->name ?? 'Unknown',
                occurredAt: $communication->contacted_at,
                meta: $communication->next_follow_up_at ? 'Next follow-up '.$communication->next_follow_up_at->format('M j, g:i A') : null,
            ));
    }

    private function noteEvents(Prospect $prospect): Collection
    {
        return $prospect->notes()
            ->with('user:id,name')
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn ($note): array => $this->item(
                type: 'note',
                label: $note->is_private ? 'Private note' : 'Note',
                body: $note->note,
                actor: $note->user?->name ?? 'Unknown',
                occurredAt: $note->created_at,
                meta: $note->is_private ? 'Private' : null,
            ));
    }

    private function appointmentEvents(Prospect $prospect): Collection
    {
        return $prospect->appointments()
            ->with(['owner:id,name', 'type:id,name'])
            ->latest('scheduled_at')
            ->limit(80)
            ->get()
            ->map(fn ($appointment): array => $this->item(
                type: 'appointment',
                label: $appointment->type?->name ?? 'Prospect appointment',
                body: $appointment->purpose ?? $appointment->notes,
                actor: $appointment->owner?->name ?? 'Unknown',
                occurredAt: $appointment->scheduled_at,
                meta: str($appointment->status)->headline()->toString(),
                url: $appointment->calendar_event_id ? route('calendar.events.show', $appointment->calendar_event_id) : null,
            ));
    }

    private function calendarEvents(Prospect $prospect): Collection
    {
        return $prospect->calendarEvents()
            ->with(['organizer:id,name', 'type:id,name'])
            ->latest('starts_at')
            ->limit(80)
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->item(
                type: 'calendar',
                label: $event->type?->name ?? 'Calendar event',
                body: $event->title,
                actor: $event->organizer?->name ?? 'Unknown',
                occurredAt: $event->starts_at,
                meta: str($event->status)->headline()->toString(),
                url: route('calendar.events.show', $event),
            ));
    }

    private function followUpEvents(Prospect $prospect): Collection
    {
        return $prospect->followups()
            ->with('assignedUser:id,name')
            ->latest('due_at')
            ->limit(80)
            ->get()
            ->map(fn ($followUp): array => $this->item(
                type: 'followup',
                label: str($followUp->followup_type ?? 'Follow-up')->replace('_', ' ')->headline()->toString(),
                body: $followUp->notes,
                actor: $followUp->assignedUser?->name ?? 'Unknown',
                occurredAt: $followUp->due_at,
                meta: str($followUp->status)->headline()->toString(),
                url: route('team.prospects.follow-ups'),
            ));
    }

    private function taskEvents(Prospect $prospect): Collection
    {
        return TaskUser::query()
            ->with(['assignee:id,name', 'task:id,title'])
            ->where('related_prospect_id', $prospect->id)
            ->latest('created_at')
            ->limit(80)
            ->get()
            ->map(fn (TaskUser $task): array => $this->item(
                type: 'task',
                label: $task->displayTitle(),
                body: $task->displayBody(),
                actor: $task->assignee?->name ?? 'Unassigned',
                occurredAt: $task->created_at,
                meta: $task->displayStatus(),
                url: route('tasks.index'),
            ));
    }

    private function fnaEvents(Prospect $prospect): Collection
    {
        return $prospect->fnaRecords()
            ->with('owner:id,name')
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn ($fna): array => $this->item(
                type: 'fna',
                label: 'FNA '.$fna->statusLabel(),
                body: $fna->title.' ('.$fna->reference_code.')',
                actor: $fna->owner?->name ?? 'Unknown',
                occurredAt: $fna->submitted_at ?? $fna->updated_at ?? $fna->created_at,
                meta: $fna->completeness_score.'% complete',
            ));
    }

    private function conversionEvents(Prospect $prospect): Collection
    {
        return $prospect->conversions()
            ->with('convertedBy:id,name')
            ->latest('converted_at')
            ->limit(80)
            ->get()
            ->map(fn ($conversion): array => $this->item(
                type: 'conversion',
                label: config('prospects.conversion_types.'.$conversion->conversion_type, str($conversion->conversion_type)->headline()->toString()),
                body: $conversion->notes ?? $conversion->policy_reference ?? $conversion->application_reference,
                actor: $conversion->convertedBy?->name ?? 'System',
                occurredAt: $conversion->converted_at,
                meta: $conversion->policy_reference ? 'Policy '.$conversion->policy_reference : null,
            ));
    }

    private function fileEvents(Prospect $prospect): Collection
    {
        return $prospect->files()
            ->with('uploader:id,name')
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn ($file): array => $this->item(
                type: 'file',
                label: 'File uploaded',
                body: $file->name,
                actor: $file->uploader?->name ?? 'Unknown',
                occurredAt: $file->created_at,
                meta: $file->mime_type,
            ));
    }

    private function item(string $type, string $label, ?string $body, ?string $actor, mixed $occurredAt, ?string $meta = null, ?string $url = null): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'body' => trim((string) $body),
            'actor' => $actor ?? 'System',
            'occurred_at_raw' => $occurredAt,
            'occurred_at' => '',
            'meta' => $meta,
            'url' => $url,
        ];
    }
}
