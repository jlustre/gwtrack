<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\Prospects\ProspectAnalyticsService;
use App\Services\Prospects\ProspectFunnelService;
use App\Support\MemberDisplayName;
use Illuminate\Support\Facades\DB;

class RecruitingPipelineService
{
    public function __construct(
        private readonly ProspectAnalyticsService $prospectAnalytics,
        private readonly ProspectFunnelService $funnels,
        private readonly DashboardStatsService $dashboardStats,
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardFor(User $viewer): array
    {
        $funnel = $this->funnels->resolveFunnel(config('recruiting-pipeline.funnel_key', 'recruiting'));
        $funnelAnalytics = $this->prospectAnalytics->funnelConversion($viewer, 'recruiting');
        $recruitingProspects = $this->recruitingProspectsQuery($viewer);

        return [
            'stats' => $this->statsFor($viewer, $recruitingProspects),
            'funnel' => array_merge($funnelAnalytics, [
                'name' => $funnel->name,
                'description' => $funnel->description,
            ]),
            'candidates' => $this->candidateRows($recruitingProspects),
            'pending_invitations' => $this->pendingInvitations($viewer),
            'active_recruits' => $this->activeRecruitRows($viewer),
            'hot_candidates' => $this->hotCandidates($recruitingProspects),
            'urls' => [
                'add_candidate' => route('team.prospects.create', ['funnel_type' => 'recruiting']),
                'sales_crm' => route('team.prospects'),
                'goals' => route('goals.index'),
                'directs' => route('team.directs'),
                'team' => route('team.index'),
                'rank_advancement' => route('rank-advancement.index'),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statsFor(User $viewer, $recruitingProspectsQuery): array
    {
        $active = (clone $recruitingProspectsQuery)
            ->where('status', 'active')
            ->where('is_archived', false);

        $allRecruiting = (clone $recruitingProspectsQuery);
        $converted = (clone $allRecruiting)->where('converted_to', 'associate')->count();
        $totalOwned = (clone $allRecruiting)->count();

        $terminalStageIds = $this->terminalStageIds();
        $prospectIds = (clone $recruitingProspectsQuery)->pluck('id');

        return [
            'active_candidates' => (clone $active)
                ->when($terminalStageIds !== [], fn ($query) => $query->whereNotIn('pipeline_stage_id', $terminalStageIds))
                ->count(),
            'hot_candidates' => (clone $active)->where('interest_level', 'hot')->count(),
            'followups_due' => (clone $active)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now())
                ->count(),
            'presentations_scheduled' => $prospectIds->isEmpty()
                ? 0
                : DB::table('prospect_appointments')
                    ->where('owner_id', $viewer->id)
                    ->whereIn('prospect_id', $prospectIds)
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '>=', now())
                    ->whereNull('deleted_at')
                    ->count(),
            'pending_invitations' => RegistrationInvitation::query()
                ->where('sponsor_id', $viewer->id)
                ->whereNotNull('prospect_id')
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'registered_this_month' => $this->registeredThisMonth($viewer),
            'direct_recruits' => $this->hierarchy->directRecruitsQuery($viewer)->count(),
            'conversion_rate' => $totalOwned > 0 ? (int) round(($converted / $totalOwned) * 100) : 0,
            'associates_converted' => $converted,
        ];
    }

    private function registeredThisMonth(User $viewer): int
    {
        $prospectIds = Prospect::query()
            ->where('owner_id', $viewer->id)
            ->whereIn('funnel_type', config('recruiting-pipeline.candidate_funnel_types', ['recruiting']))
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($prospectIds->isEmpty()) {
            return 0;
        }

        return DB::table('prospect_conversions')
            ->whereIn('prospect_id', $prospectIds)
            ->where('conversion_type', 'associate')
            ->whereNotNull('created_user_id')
            ->whereBetween('converted_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * @return list<int>
     */
    private function terminalStageIds(): array
    {
        $funnelId = DB::table('prospect_funnels')
            ->where('key', config('recruiting-pipeline.funnel_key', 'recruiting'))
            ->value('id');

        if (! $funnelId) {
            return [];
        }

        return DB::table('prospect_funnel_stages')
            ->where('prospect_funnel_id', $funnelId)
            ->where('is_terminal', true)
            ->pluck('pipeline_stage_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function recruitingProspectsQuery(User $viewer)
    {
        return Prospect::query()
            ->where('owner_id', $viewer->id)
            ->whereIn('funnel_type', config('recruiting-pipeline.candidate_funnel_types', ['recruiting']))
            ->whereNull('deleted_at');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function candidateRows($query): array
    {
        $terminalStageIds = $this->terminalStageIds();
        $limit = (int) config('recruiting-pipeline.candidate_list_limit', 25);

        return (clone $query)
            ->with(['stage:id,name,slug'])
            ->where('status', 'active')
            ->where('is_archived', false)
            ->when($terminalStageIds !== [], fn ($builder) => $builder->whereNotIn('pipeline_stage_id', $terminalStageIds))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('next_follow_up_at')
            ->limit($limit)
            ->get()
            ->map(fn (Prospect $prospect): array => [
                'id' => $prospect->id,
                'name' => $prospect->displayName(),
                'pipeline' => $prospect->pipelineLabel(),
                'funnel_type' => $prospect->funnel_type,
                'stage' => $prospect->stage?->name ?? '—',
                'stage_slug' => $prospect->stage?->slug,
                'interest_level' => $prospect->interest_level,
                'priority' => $prospect->priority,
                'next_follow_up_at' => $prospect->next_follow_up_at?->format('M j, Y g:i A'),
                'is_overdue' => $prospect->next_follow_up_at !== null && $prospect->next_follow_up_at->isPast(),
                'converted_to' => $prospect->converted_to,
                'profile_url' => route('team.prospects.records.show', $prospect),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function hotCandidates($query): array
    {
        return (clone $query)
            ->with('stage:id,name')
            ->where('status', 'active')
            ->where('is_archived', false)
            ->where('interest_level', 'hot')
            ->orderBy('next_follow_up_at')
            ->limit(5)
            ->get()
            ->map(fn (Prospect $prospect): array => [
                'id' => $prospect->id,
                'name' => $prospect->displayName(),
                'stage' => $prospect->stage?->name ?? '—',
                'profile_url' => route('team.prospects.records.show', $prospect),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pendingInvitations(User $viewer): array
    {
        $limit = (int) config('recruiting-pipeline.invitation_list_limit', 10);

        return RegistrationInvitation::query()
            ->with(['prospect:id,first_name,last_name,preferred_name'])
            ->where('sponsor_id', $viewer->id)
            ->whereNotNull('prospect_id')
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (RegistrationInvitation $invitation): array {
                $prospect = $invitation->prospect;

                return [
                    'id' => $invitation->id,
                    'prospect_name' => $prospect?->displayName() ?? ($invitation->email ?: 'Recruit candidate'),
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at?->format('M j, Y') ?? '—',
                    'invitation_url' => $invitation->invitationUrl(),
                    'prospect_url' => $prospect ? route('team.prospects.records.show', $prospect) : null,
                    'created_at' => $invitation->created_at?->format('M j, Y') ?? '—',
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeRecruitRows(User $viewer): array
    {
        $limit = (int) config('recruiting-pipeline.active_recruit_limit', 25);

        return $this->hierarchy->directRecruitsQuery($viewer)
            ->with(['rank', 'profile'])
            ->where('is_active', true)
            ->orderByDesc('joined_at')
            ->limit($limit)
            ->get()
            ->map(fn (User $member): array => $this->serializeRecruitJourney($member))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRecruitJourney(User $member): array
    {
        $onboarding = $this->dashboardStats->onboardingPercent($member);
        $licensing = $this->dashboardStats->licensingPercent($member);
        $fap = $this->dashboardStats->apprenticeshipPercent($member);
        $stage = $this->resolveJourneyStage($onboarding, $licensing, $fap);

        return [
            'id' => $member->id,
            'name' => MemberDisplayName::for($member),
            'rank' => $member->rank?->name,
            'joined_at' => $member->joined_at?->format('M j, Y') ?? '—',
            'journey_stage' => $stage,
            'journey_stage_label' => config('recruiting-pipeline.journey_stages.'.$stage, 'In progress'),
            'onboarding_pct' => $onboarding,
            'licensing_pct' => $licensing,
            'fap_pct' => $fap,
            'profile_url' => route('team.member.profile', $member),
        ];
    }

    private function resolveJourneyStage(int $onboarding, int $licensing, int $fap): string
    {
        $thresholds = config('recruiting-pipeline.journey_thresholds', []);

        if ($fap >= ($thresholds['fap'] ?? 100) && $licensing >= ($thresholds['licensing'] ?? 100)) {
            return 'licensed_producer';
        }

        if ($licensing >= ($thresholds['licensing'] ?? 100)) {
            return 'fap';
        }

        if ($onboarding >= ($thresholds['onboarding'] ?? 100)) {
            return 'licensing';
        }

        if ($onboarding > 0) {
            return 'onboarding';
        }

        return 'registered';
    }
}
