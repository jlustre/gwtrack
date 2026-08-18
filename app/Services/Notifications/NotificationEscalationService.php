<?php

namespace App\Services\Notifications;

use App\Models\MentorAssignment;
use App\Models\NotificationEscalationLog;
use App\Models\NotificationEscalationRule;
use App\Models\ProspectFollowUp;
use App\Models\User;
use App\Models\TaskUser;
use App\Services\MemberUplineService;

class NotificationEscalationService
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
        private readonly MemberUplineService $memberUpline,
    ) {}

    public function evaluateAll(): int
    {
        $fired = 0;

        $rules = NotificationEscalationRule::query()
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $fired += match ($rule->condition_type) {
                'trainee_inactivity_days' => $this->evaluateTraineeInactivity($rule),
                'prospect_follow_up_overdue' => $this->evaluateProspectFollowUps($rule),
                'task_overdue' => $this->evaluateOverdueTasks($rule),
                default => 0,
            };
        }

        return $fired;
    }

    private function evaluateTraineeInactivity(NotificationEscalationRule $rule): int
    {
        $fired = 0;
        $steps = collect($rule->escalation_steps ?? [])->values();

        if ($steps->isEmpty()) {
            return 0;
        }

        $traineeIds = MentorAssignment::query()
            ->where('status', 'active')
            ->pluck('apprentice_id')
            ->unique();

        $trainees = User::query()
            ->whereIn('id', $traineeIds)
            ->whereNull('deleted_at')
            ->get();

        foreach ($trainees as $trainee) {
            $inactiveDays = $this->inactiveDaysFor($trainee);

            foreach ($steps as $index => $step) {
                $threshold = (int) ($step['after_days'] ?? 0);

                if ($inactiveDays < $threshold) {
                    continue;
                }

                if ($this->shouldSkipStep($rule, $trainee, $index, oncePerSubject: true)) {
                    continue;
                }

                $recipientIds = $this->resolveStepRecipients($trainee, $step['notify'] ?? []);

                if ($recipientIds === []) {
                    continue;
                }

                $triggerCode = $step['trigger_code'] ?? 'trainee_inactivity_escalation';
                $priority = $step['priority'] ?? 'medium';
                $typeCode = ! empty($step['create_risk_alert']) ? 'risk_alert' : 'escalation';

                $this->notifications->dispatch($triggerCode, [
                    'queue' => true,
                    'recipients' => ['user_ids' => $recipientIds],
                    'module' => $rule->module ?? 'mentorship',
                    'type_code' => $typeCode,
                    'priority' => $priority,
                    'related' => ['type' => User::class, 'id' => $trainee->id],
                    'related_user_id' => $trainee->id,
                    'template_data' => [
                        'trainee_name' => $trainee->name,
                        'member_name' => $trainee->name,
                        'inactive_days' => (string) $inactiveDays,
                    ],
                    'title' => $this->inactivityTitle($trainee, $inactiveDays, $priority),
                    'message' => "{$trainee->name} has been inactive for {$inactiveDays} days.",
                    'action_link' => [
                        'route' => 'cfm.portal',
                        'params' => ['trainee' => $trainee->id],
                        'label' => 'View trainee',
                    ],
                ]);

                NotificationEscalationLog::query()->create([
                    'escalation_rule_id' => $rule->id,
                    'subject_type' => User::class,
                    'subject_id' => $trainee->id,
                    'step_index' => $index,
                    'notified_user_ids' => $recipientIds,
                    'trigger_code' => $triggerCode,
                    'fired_at' => now(),
                ]);

                $fired++;
            }
        }

        return $fired;
    }

    private function evaluateProspectFollowUps(NotificationEscalationRule $rule): int
    {
        $fired = 0;
        $step = collect($rule->escalation_steps ?? [])->first() ?? [];
        $triggerCode = $step['trigger_code'] ?? 'prospect_follow_up_overdue';

        $followUps = ProspectFollowUp::query()
            ->with(['prospect', 'assignedUser'])
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_at', '<', now())
            ->get();

        foreach ($followUps as $followUp) {
            if ($followUp->status !== 'overdue') {
                $followUp->update(['status' => 'overdue']);
            }

            if ($this->shouldSkipStep($rule, $followUp, 0, oncePerSubject: false)) {
                continue;
            }

            $owner = $followUp->assignedUser;

            if (! $owner) {
                continue;
            }

            $prospectName = $followUp->prospect?->displayName() ?? 'Prospect';

            $this->notifications->dispatch($triggerCode, [
                'queue' => true,
                'recipients' => [$owner->id],
                'module' => 'prospect',
                'priority' => $step['priority'] ?? 'high',
                'related' => ['type' => $followUp->prospect::class, 'id' => $followUp->prospect_id],
                'template_data' => [
                    'prospect_name' => $prospectName,
                    'message' => "Follow-up overdue for {$prospectName}.",
                ],
                'title' => "Follow-up overdue: {$prospectName}",
                'message' => "A prospect follow-up is overdue for {$prospectName}.",
                'action_link' => [
                    'route' => 'team.prospects.records.show',
                    'params' => ['prospect' => $followUp->prospect_id],
                    'label' => 'View prospect',
                ],
            ]);

            NotificationEscalationLog::query()->create([
                'escalation_rule_id' => $rule->id,
                'subject_type' => ProspectFollowUp::class,
                'subject_id' => $followUp->id,
                'step_index' => 0,
                'notified_user_ids' => [$owner->id],
                'trigger_code' => $triggerCode,
                'fired_at' => now(),
            ]);

            $fired++;
        }

        return $fired;
    }

    private function evaluateOverdueTasks(NotificationEscalationRule $rule): int
    {
        $fired = 0;
        $step = collect($rule->escalation_steps ?? [])->first() ?? [];
        $triggerCode = $step['trigger_code'] ?? 'task_overdue';

        $tasks = TaskUser::query()
            ->whereNull('completed_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($tasks as $task) {
            if ($this->shouldSkipStep($rule, $task, 0, oncePerSubject: false)) {
                continue;
            }

            $assigneeId = $task->assignee_id;

            if (! $assigneeId) {
                continue;
            }

            $this->notifications->dispatch($triggerCode, [
                'queue' => true,
                'recipients' => [$assigneeId],
                'module' => 'task',
                'priority' => $step['priority'] ?? 'high',
                'related' => ['type' => TaskUser::class, 'id' => $task->id],
                'template_data' => [
                    'task_name' => $task->displayTitle(),
                    'deadline' => $task->due_date?->format('M j, Y') ?? '',
                ],
                'title' => "Task overdue: {$task->displayTitle()}",
                'message' => "The task \"{$task->displayTitle()}\" is past its due date.",
                'action_link' => [
                    'route' => 'tasks.index',
                    'label' => 'View tasks',
                ],
            ]);

            NotificationEscalationLog::query()->create([
                'escalation_rule_id' => $rule->id,
                'subject_type' => TaskUser::class,
                'subject_id' => $task->id,
                'step_index' => 0,
                'notified_user_ids' => [$assigneeId],
                'trigger_code' => $triggerCode,
                'fired_at' => now(),
            ]);

            $fired++;
        }

        return $fired;
    }

    /**
     * @param  list<string>  $roles
     * @return list<int>
     */
    private function resolveStepRecipients(User $trainee, array $roles): array
    {
        $trainee->loadMissing(['sponsor', 'mentor']);

        $ids = [];

        foreach ($roles as $role) {
            match ($role) {
                'cfm', 'mentor' => $trainee->mentor_id ? $ids[] = (int) $trainee->mentor_id : null,
                'sponsor' => $trainee->sponsor_id ? $ids[] = (int) $trainee->sponsor_id : null,
                'agency_owner' => ($ao = $this->memberUpline->agencyOwner($trainee)) ? $ids[] = $ao->id : null,
                default => null,
            };
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function shouldSkipStep(
        NotificationEscalationRule $rule,
        object $subject,
        int $stepIndex,
        bool $oncePerSubject,
    ): bool {
        $query = NotificationEscalationLog::query()
            ->where('escalation_rule_id', $rule->id)
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->id)
            ->where('step_index', $stepIndex)
            ->latest('fired_at');

        if ($oncePerSubject) {
            return $query->exists();
        }

        $existing = $query->first();

        if (! $existing) {
            return false;
        }

        $cooldownHours = $rule->cooldown_hours ?: config('notifications.escalation.cooldown_hours', 24);

        return $existing->fired_at->gt(now()->subHours($cooldownHours));
    }

    private function inactiveDaysFor(User $trainee): int
    {
        $reference = $trainee->last_login_at ?? $trainee->joined_at ?? $trainee->created_at;

        return (int) $reference->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    private function inactivityTitle(User $trainee, int $days, string $priority): string
    {
        if (in_array($priority, ['critical', 'urgent'], true)) {
            return "Risk alert: {$trainee->name} inactive {$days} days";
        }

        return "Trainee inactivity: {$trainee->name} ({$days} days)";
    }
}
