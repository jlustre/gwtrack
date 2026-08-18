<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectFollowUp;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProspectAiCoachService
{
    public function __construct(private ProspectFollowUpEngine $followUpEngine) {}

    /**
     * @return array{
     *     recommendations: list<array<string, mixed>>,
     *     stalled_prospects: list<array<string, mixed>>,
     *     high_value_opportunities: list<array<string, mixed>>,
     *     scored_prospects: list<array<string, mixed>>,
     * }
     */
    public function recommendationsFor(User $user): array
    {
        $prospects = Prospect::query()
            ->with(['stage'])
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->get();

        $recommendations = collect();

        foreach ($prospects as $prospect) {
            $recommendations = $recommendations->merge(
                $this->buildRecommendationsForProspect($prospect, $user)
            );
        }

        $sorted = $this->sortByPriority($recommendations);

        return [
            'recommendations' => $sorted->values()->all(),
            'stalled_prospects' => $this->stalledProspects($prospects)->values()->all(),
            'high_value_opportunities' => $this->highValueOpportunities($prospects)->values()->all(),
            'scored_prospects' => $prospects
                ->map(fn (Prospect $prospect): array => $this->scoreProspect($prospect))
                ->sortByDesc('lead_score')
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function intelligenceForProspect(Prospect $prospect, ?User $user = null): array
    {
        $prospect->loadMissing(['stage', 'funnel']);
        $user ??= auth()->user();

        return [
            'score' => $this->scoreProspect($prospect),
            'next_best_action' => $this->nextBestAction($prospect, $user),
            'follow_up_suggestions' => $this->followUpSuggestions($prospect, $user),
            'meeting_summary' => $this->meetingSummary($prospect),
            'conversation_summary' => $this->conversationSummary($prospect),
            'drafts' => $this->draftAssistance($prospect),
            'forecast' => $this->forecast($prospect),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function scoreProspect(Prospect $prospect): array
    {
        $prospect->loadMissing(['stage', 'funnel']);
        $weights = config('prospects.ai_scoring', []);
        $reasons = [];
        $leadScore = 0;

        $interestPoints = (int) data_get($weights, 'interest_level.'.$prospect->interest_level, 0);
        if ($interestPoints > 0) {
            $leadScore += $interestPoints;
            $reasons[] = str($prospect->interest_level)->headline()." interest adds {$interestPoints} points.";
        }

        $priorityPoints = (int) data_get($weights, 'priority.'.$prospect->priority, 0);
        if ($priorityPoints > 0) {
            $leadScore += $priorityPoints;
            $reasons[] = str($prospect->priority)->headline()." priority adds {$priorityPoints} points.";
        }

        if ($prospect->interest_score !== null) {
            $interestScorePoints = min(30, (int) $prospect->interest_score * 3);
            $leadScore += $interestScorePoints;
            $reasons[] = "Interest score {$prospect->interest_score}/10 adds {$interestScorePoints} points.";
        }

        $stageWeight = $this->stageConversionWeight($prospect);
        if ($stageWeight > 0) {
            $stagePoints = min(20, (int) round($stageWeight / 5));
            $leadScore += $stagePoints;
            $reasons[] = "Current funnel stage adds {$stagePoints} points.";
        }

        $lastContact = $prospect->last_contacted_at ?? $prospect->last_activity_at;
        if ($lastContact?->gte(now()->subDays(3))) {
            $points = (int) data_get($weights, 'recency.contacted_3d', 0);
            $leadScore += $points;
            $reasons[] = 'Recent contact within 3 days increases confidence.';
        } elseif ($lastContact?->gte(now()->subDays(7))) {
            $points = (int) data_get($weights, 'recency.contacted_7d', 0);
            $leadScore += $points;
            $reasons[] = 'Recent contact within 7 days keeps this active.';
        } elseif (($lastContact ?? $prospect->created_at)?->lte(now()->subDays(14))) {
            $penalty = (int) data_get($weights, 'recency.stale_14d_penalty', 0);
            $leadScore += $penalty;
            $reasons[] = 'No recent contact reduces confidence.';
        }

        if ($this->hasUpcomingMeeting($prospect)) {
            $leadScore += (int) data_get($weights, 'calendar.upcoming_meeting', 0);
            $reasons[] = 'Upcoming calendar meeting detected.';
        }

        if ($this->hasCompletedMeeting($prospect)) {
            $leadScore += (int) data_get($weights, 'calendar.completed_meeting', 0);
            $reasons[] = 'Completed meeting adds momentum.';
        }

        if ($this->hasRecentNoShow($prospect)) {
            $leadScore += (int) data_get($weights, 'calendar.no_show_penalty', 0);
            $reasons[] = 'Recent no-show reduces confidence.';
        }

        $leadScore = max(0, min(100, $leadScore));
        $recruitingProbability = $this->recruitingProbability($prospect);

        $prospect->forceFill(['engagement_score' => $leadScore])->save();
        $prospect->salesOpportunity?->forceFill(['probability' => $leadScore])->save();
        $prospect->recruitingOpportunity?->forceFill(['probability' => $recruitingProbability])->save();

        return [
            'prospect_id' => $prospect->id,
            'prospect_name' => $prospect->displayName(),
            'lead_score' => $leadScore,
            'recruiting_probability' => $recruitingProbability,
            'confidence' => $this->confidenceBand($leadScore),
            'reasons' => array_slice($reasons, 0, 5),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recommendationsForProspect(Prospect $prospect, ?User $user = null): array
    {
        $prospect->loadMissing('stage');
        $user ??= auth()->user();

        return $this->sortByPriority(
            $this->buildRecommendationsForProspect($prospect, $user)
        )->values()->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRecommendationsForProspect(Prospect $prospect, ?User $user): Collection
    {
        if ($prospect->status !== 'active' || $prospect->is_archived) {
            return collect();
        }

        $rules = config('prospects.ai_coach_rules', []);
        $items = collect();

        foreach ($rules as $ruleKey => $rule) {
            if (! is_array($rule)) {
                continue;
            }

            if ($ruleKey === 'overdue_followup') {
                if ($this->matchesOverdueFollowUp($prospect, $user)) {
                    $items->push($this->formatRecommendation($prospect, $ruleKey, $rule));
                }

                continue;
            }

            $engineRule = config("prospects.follow_up_engine_rules.{$ruleKey}", $rule);

            if ($this->followUpEngine->evaluateRule($ruleKey, is_array($engineRule) ? $engineRule : $rule, $prospect)) {
                $items->push($this->formatRecommendation($prospect, $ruleKey, $rule));
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return array<string, mixed>
     */
    private function formatRecommendation(Prospect $prospect, string $ruleKey, array $rule): array
    {
        $offsetDays = (int) ($rule['suggested_due_offset_days'] ?? 0);
        $suggestedDue = $offsetDays === 0 && ($rule['suggested_action'] ?? '') === 'act_today'
            ? now()->endOfDay()
            : now()->addDays($offsetDays);

        return [
            'rule_key' => $ruleKey,
            'prospect_id' => $prospect->id,
            'prospect_name' => $prospect->displayName(),
            'score' => $this->scoreProspect($prospect)['lead_score'],
            'priority' => $this->normalizePriority($rule['priority'] ?? 'medium'),
            'message' => $rule['message'] ?? ($rule['title'] ?? 'Follow up recommended'),
            'suggested_action' => $rule['suggested_action'] ?? 'log_call',
            'suggested_due' => $suggestedDue->toIso8601String(),
        ];
    }

    private function normalizePriority(string $priority): string
    {
        return match ($priority) {
            'urgent', 'high' => 'high',
            'low' => 'low',
            default => 'medium',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $recommendations
     * @return Collection<int, array<string, mixed>>
     */
    private function sortByPriority(Collection $recommendations): Collection
    {
        $order = ['high' => 1, 'medium' => 2, 'low' => 3];

        return $recommendations->sortBy(fn (array $item): int => $order[$item['priority']] ?? 4);
    }

    private function matchesOverdueFollowUp(Prospect $prospect, ?User $user): bool
    {
        if ($prospect->next_follow_up_at?->isPast()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return ProspectFollowUp::query()
            ->where('prospect_id', $prospect->id)
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_at', '<', now())
            ->exists();
    }

    /**
     * @param  Collection<int, Prospect>  $prospects
     * @return Collection<int, array<string, mixed>>
     */
    private function stalledProspects(Collection $prospects): Collection
    {
        $stalledSlugs = ['application-submitted', 'registration-link-sent'];

        return $prospects
            ->filter(function (Prospect $prospect) use ($stalledSlugs): bool {
                if (! in_array($prospect->stage?->slug, $stalledSlugs, true)) {
                    return false;
                }

                $ruleKey = $prospect->stage?->slug === 'application-submitted'
                    ? 'application_stalled'
                    : 'registration_incomplete';

                $engineRule = config("prospects.follow_up_engine_rules.{$ruleKey}", []);

                return is_array($engineRule)
                    && $this->followUpEngine->evaluateRule($ruleKey, $engineRule, $prospect);
            })
            ->map(fn (Prospect $prospect): array => [
                'prospect_id' => $prospect->id,
                'prospect_name' => $prospect->displayName(),
                'stage' => $prospect->stage?->name,
                'stage_slug' => $prospect->stage?->slug,
                'last_activity_at' => $prospect->last_activity_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  Collection<int, Prospect>  $prospects
     * @return Collection<int, array<string, mixed>>
     */
    private function highValueOpportunities(Collection $prospects): Collection
    {
        return $prospects
            ->filter(function (Prospect $prospect): bool {
                if ($prospect->interest_level !== 'hot') {
                    return false;
                }

                $recentActivity = ($prospect->last_activity_at ?? $prospect->last_contacted_at)?->gte(now()->subDays(7));
                $highScore = (int) ($prospect->interest_score ?? 0) >= 7;

                return $recentActivity || $highScore;
            })
            ->map(fn (Prospect $prospect): array => [
                'prospect_id' => $prospect->id,
                'prospect_name' => $prospect->displayName(),
                'interest_level' => $prospect->interest_level,
                'interest_score' => $prospect->interest_score,
                'stage' => $prospect->stage?->name,
                'last_activity_at' => $prospect->last_activity_at?->toIso8601String(),
            ]);
    }

    /**
     * @return array{label: string, action: string, reason: string, due: string}
     */
    private function nextBestAction(Prospect $prospect, ?User $user): array
    {
        if ($this->matchesOverdueFollowUp($prospect, $user)) {
            return [
                'label' => 'Complete overdue follow-up',
                'action' => 'act_today',
                'reason' => 'A pending follow-up is already past due.',
                'due' => now()->endOfDay()->toIso8601String(),
            ];
        }

        if (! $this->hasUpcomingMeeting($prospect) && in_array($prospect->interest_level, ['hot', 'warm'], true)) {
            return [
                'label' => 'Schedule next meeting',
                'action' => 'schedule_meeting',
                'reason' => 'Interest is active but no future calendar commitment exists.',
                'due' => now()->addDay()->endOfDay()->toIso8601String(),
            ];
        }

        if (in_array($prospect->stage?->slug, ['application-submitted', 'registration-link-sent'], true)) {
            return [
                'label' => 'Escalate stalled stage',
                'action' => 'escalate',
                'reason' => 'The prospect is in a stage that usually needs confirmation.',
                'due' => now()->endOfDay()->toIso8601String(),
            ];
        }

        return [
            'label' => 'Log a relationship touch',
            'action' => 'log_activity',
            'reason' => 'Keep the timeline fresh and maintain momentum.',
            'due' => now()->addDays(2)->endOfDay()->toIso8601String(),
        ];
    }

    /**
     * @return list<array{label: string, due: string, reason: string}>
     */
    private function followUpSuggestions(Prospect $prospect, ?User $user): array
    {
        $suggestions = [];

        if ($this->matchesOverdueFollowUp($prospect, $user)) {
            $suggestions[] = [
                'label' => 'Resolve overdue follow-up',
                'due' => now()->endOfDay()->toIso8601String(),
                'reason' => 'Open follow-up is overdue.',
            ];
        }

        if (! $this->hasUpcomingMeeting($prospect)) {
            $suggestions[] = [
                'label' => 'Book a discovery or review meeting',
                'due' => now()->addDays(2)->endOfDay()->toIso8601String(),
                'reason' => 'No upcoming calendar event is linked.',
            ];
        }

        if (($prospect->last_contacted_at ?? $prospect->created_at)?->lte(now()->subDays(7))) {
            $suggestions[] = [
                'label' => 'Send check-in message',
                'due' => now()->endOfDay()->toIso8601String(),
                'reason' => 'No contact has been logged in the last week.',
            ];
        }

        return array_slice($suggestions, 0, 3);
    }

    /**
     * @return array{summary: string, upcoming: int, completed: int, cancelled_or_no_show: int}
     */
    private function meetingSummary(Prospect $prospect): array
    {
        $rows = DB::table('prospect_appointments')
            ->where('prospect_id', $prospect->id)
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $upcoming = (int) DB::table('prospect_appointments')
            ->where('prospect_id', $prospect->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->whereNull('deleted_at')
            ->count();

        $completed = (int) ($rows['completed'] ?? 0);
        $missed = (int) ($rows['cancelled'] ?? 0) + (int) ($rows['no_show'] ?? 0);

        return [
            'summary' => $upcoming > 0
                ? 'Upcoming meeting is already scheduled.'
                : ($completed > 0 ? 'Past meeting completed; next follow-up should be clear.' : 'No completed meetings logged yet.'),
            'upcoming' => $upcoming,
            'completed' => $completed,
            'cancelled_or_no_show' => $missed,
        ];
    }

    /**
     * @return array{summary: string, latest_touch: string|null, touch_count_30d: int}
     */
    private function conversationSummary(Prospect $prospect): array
    {
        $latestCommunication = $prospect->communications()->latest('contacted_at')->first();
        $latestActivity = $prospect->activities()->latest('occurred_at')->first();

        $latestTouch = collect([
            $latestCommunication?->contacted_at,
            $latestActivity?->occurred_at,
        ])->filter()->sortDesc()->first();

        $touchCount = $prospect->communications()->where('contacted_at', '>=', now()->subDays(30))->count()
            + $prospect->activities()->where('occurred_at', '>=', now()->subDays(30))->count();

        $summary = $latestCommunication?->notes
            ?: $latestActivity?->notes
            ?: 'No conversation notes have been captured yet.';

        return [
            'summary' => str($summary)->limit(180)->toString(),
            'latest_touch' => $latestTouch?->toIso8601String(),
            'touch_count_30d' => $touchCount,
        ];
    }

    /**
     * @return array{sms: string, email_subject: string, email_body: string}
     */
    private function draftAssistance(Prospect $prospect): array
    {
        $name = $prospect->preferred_name ?: $prospect->first_name;
        $nextAction = $this->nextBestAction($prospect, auth()->user());

        return [
            'sms' => "Hi {$name}, just checking in. Would a quick call this week work to cover the next step?",
            'email_subject' => 'Next step',
            'email_body' => "Hi {$name},\n\nI wanted to follow up and make the next step easy. {$nextAction['reason']}\n\nWould you be open to a quick conversation this week?\n\nBest,",
        ];
    }

    /**
     * @return array{label: string, score: int, rationale: string}
     */
    private function forecast(Prospect $prospect): array
    {
        $score = $this->scoreProspect($prospect)['lead_score'];

        return [
            'label' => $score >= 75 ? 'Strong' : ($score >= 45 ? 'Moderate' : 'Needs nurture'),
            'score' => $score,
            'rationale' => $score >= 75
                ? 'Recent engagement and pipeline signals indicate strong momentum.'
                : ($score >= 45 ? 'Some positive signals are present, but the next action matters.' : 'Engagement is limited or stale; nurture before forecasting conversion.'),
        ];
    }

    private function recruitingProbability(Prospect $prospect): int
    {
        if (! in_array($prospect->funnel_type, ['recruiting', 'both'], true)) {
            return 0;
        }

        $weights = config('prospects.ai_scoring.recruiting_traits', []);
        $score = 20;

        foreach ((array) ($prospect->qualification_traits ?? []) as $trait => $enabled) {
            if ($enabled) {
                $score += (int) ($weights[$trait] ?? 0);
            }
        }

        if (str_contains((string) $prospect->stage?->slug, 'registration') || str_contains((string) $prospect->stage?->slug, 'associate')) {
            $score += 25;
        }

        if ($prospect->last_activity_at?->gte(now()->subDays(7))) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    private function stageConversionWeight(Prospect $prospect): float
    {
        if (! $prospect->prospect_funnel_id || ! $prospect->pipeline_stage_id) {
            return 0.0;
        }

        return (float) DB::table('prospect_funnel_stages')
            ->where('prospect_funnel_id', $prospect->prospect_funnel_id)
            ->where('pipeline_stage_id', $prospect->pipeline_stage_id)
            ->value('conversion_weight');
    }

    private function hasUpcomingMeeting(Prospect $prospect): bool
    {
        return DB::table('prospect_appointments')
            ->where('prospect_id', $prospect->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->whereNull('deleted_at')
            ->exists();
    }

    private function hasCompletedMeeting(Prospect $prospect): bool
    {
        return DB::table('prospect_appointments')
            ->where('prospect_id', $prospect->id)
            ->where('status', 'completed')
            ->where('scheduled_at', '>=', now()->subDays(45))
            ->whereNull('deleted_at')
            ->exists();
    }

    private function hasRecentNoShow(Prospect $prospect): bool
    {
        return DB::table('prospect_appointments')
            ->where('prospect_id', $prospect->id)
            ->where('status', 'no_show')
            ->where('scheduled_at', '>=', now()->subDays(30))
            ->whereNull('deleted_at')
            ->exists();
    }

    private function confidenceBand(int $score): string
    {
        return $score >= 75 ? 'high' : ($score >= 45 ? 'medium' : 'low');
    }
}
