<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectFollowUp;
use App\Models\User;
use Illuminate\Support\Collection;

class ProspectFollowUpEngine
{
    public function __construct(private ProspectTaskBridge $tasks) {}

    public function runForUser(User $user): int
    {
        $created = 0;
        $rules = config('prospects.follow_up_engine_rules', []);

        $prospects = Prospect::query()
            ->with(['stage'])
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->get();

        foreach ($rules as $ruleKey => $rule) {
            if (! is_array($rule)) {
                continue;
            }

            foreach ($prospects as $prospect) {
                if ($this->evaluateRule($ruleKey, $rule, $prospect)) {
                    $followUp = $this->createFollowUp($prospect, $user, $ruleKey, $rule);

                    if ($followUp) {
                        $created++;

                        if (! empty($rule['create_task'])) {
                            $this->tasks->createFromStageTemplate($prospect, $user, [
                                'title' => $rule['task_title'] ?? ($rule['title'] ?? 'Prospect follow-up'),
                                'description' => $rule['notes'] ?? $rule['title'] ?? null,
                                'priority' => $rule['priority'] ?? 'medium',
                                'offset_days' => 0,
                            ]);
                        }
                    }
                }
            }
        }

        return $created;
    }

    public function runForAllOwners(): int
    {
        $created = 0;

        $ownerIds = Prospect::query()
            ->where('status', 'active')
            ->where('is_archived', false)
            ->distinct()
            ->pluck('owner_id');

        foreach (User::query()->whereIn('id', $ownerIds)->get() as $user) {
            $created += $this->runForUser($user);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    public function evaluateRule(string $ruleKey, array $rule, Prospect $prospect): bool
    {
        return match ($ruleKey) {
            'no_contact_7d' => $this->matchesNoContact($prospect, (int) ($rule['days_threshold'] ?? 7)),
            'hot_inactive_3d' => $this->matchesHotInactive($prospect, (int) ($rule['days_threshold'] ?? 3)),
            'presentation_no_followup' => $this->matchesPresentationNoFollowup($prospect, (int) ($rule['days_threshold'] ?? 2)),
            'registration_incomplete' => $this->matchesStageInactiveDays($prospect, $rule['stage_slug'] ?? 'registration-link-sent', (int) ($rule['days_threshold'] ?? 5)),
            'application_stalled' => $this->matchesStageInactiveDays($prospect, $rule['stage_slug'] ?? 'application-submitted', (int) ($rule['days_threshold'] ?? 14)),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function createFollowUp(Prospect $prospect, User $user, string $ruleKey, array $rule): ?ProspectFollowUp
    {
        $followupType = $rule['followup_type'] ?? $ruleKey;

        $exists = ProspectFollowUp::query()
            ->where('prospect_id', $prospect->id)
            ->where('assigned_user_id', $user->id)
            ->where('followup_type', $followupType)
            ->whereIn('status', ['pending', 'overdue'])
            ->exists();

        if ($exists) {
            return null;
        }

        return ProspectFollowUp::create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $user->id,
            'due_at' => now(),
            'followup_type' => $followupType,
            'priority' => $rule['priority'] ?? 'medium',
            'status' => 'pending',
            'notes' => $rule['notes'] ?? ($rule['title'] ?? null),
        ]);
    }

    private function matchesNoContact(Prospect $prospect, int $days): bool
    {
        if ($prospect->status !== 'active' || $prospect->is_archived) {
            return false;
        }

        $reference = $prospect->last_contacted_at ?? $prospect->created_at;

        return $reference->lte(now()->subDays($days));
    }

    private function matchesHotInactive(Prospect $prospect, int $days): bool
    {
        if (! in_array($prospect->interest_level, ['hot'], true)) {
            return false;
        }

        $reference = $prospect->last_activity_at ?? $prospect->last_contacted_at ?? $prospect->created_at;

        return $reference->lte(now()->subDays($days));
    }

    private function matchesPresentationNoFollowup(Prospect $prospect, int $days): bool
    {
        if ($prospect->stage?->slug !== 'presentation-completed') {
            return false;
        }

        $reference = $prospect->last_contacted_at ?? $prospect->last_activity_at ?? $prospect->updated_at;

        return $reference->lte(now()->subDays($days));
    }

    private function matchesStageInactiveDays(Prospect $prospect, string $stageSlug, int $days): bool
    {
        if ($prospect->stage?->slug !== $stageSlug) {
            return false;
        }

        $reference = $prospect->last_activity_at ?? $prospect->updated_at;

        return $reference->lte(now()->subDays($days));
    }

    /**
     * @return Collection<int, string>
     */
    public function stageSlugsForRule(string $ruleKey): Collection
    {
        $rule = config("prospects.follow_up_engine_rules.{$ruleKey}", []);

        if (! is_array($rule) || ! isset($rule['stage_slug'])) {
            return collect();
        }

        return collect([$rule['stage_slug']]);
    }
}
