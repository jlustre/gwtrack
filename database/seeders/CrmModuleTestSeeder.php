<?php

namespace Database\Seeders;

use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\Rank;
use App\Models\RegistrationInvitation;
use App\Models\Team;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Prospects\CrmRelationshipExpansionService;
use App\Services\Prospects\ProspectAiCoachService;
use App\Services\Prospects\ProspectCalendarBridge;
use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One-command CRM module QA stack: login account, sales prospects, recruiting candidates, and direct recruits.
 *
 * Run after foundation seeders (or let this seeder call them):
 *   php artisan db:seed --class=CrmModuleTestSeeder
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │ PRIMARY LOGIN                                                            │
 * │   Email:    crm-test@gwtrack.com                                        │
 * │   Password: Password123                                                  │
 * ├──────────────────────────────────────────────────────────────────────────┤
 * │ SUPPORTING ACCOUNTS                                                      │
 * │   crm-test-cfm@gwtrack.com      / Password123  (CFM · shared prospects)│
 * │   crm-recruit-onboarding@gwtrack.com / Password123  (direct recruit)    │
 * │   crm-recruit-licensing@gwtrack.com  / Password123  (direct recruit)    │
 * │   crm-recruit-active@gwtrack.com     / Password123  (direct recruit)    │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * Start at:
 *   /team/prospects          — sales CRM
 *   /team/recruiting         — recruiting pipeline
 */
class CrmModuleTestSeeder extends Seeder
{
    private User $owner;

    private User $cfm;

    private int $teamId;

    private int $insuranceFunnelId;

    private int $recruitingFunnelId;

    public function run(): void
    {
        $this->ensureFoundation();

        $this->teamId = $this->teamId();
        $faRankId = (int) Rank::query()->where('code', 'TA')->value('id');
        $sfaRankId = (int) (Rank::query()->where('code', 'ASC')->value('id') ?: $faRankId);

        $this->cfm = $this->user(
            'crm-test-cfm@gwtrack.com',
            'CRM Test CFM',
            'certified-field-mentor',
            $sfaRankId,
        );

        $this->owner = $this->user(
            'crm-test@gwtrack.com',
            'CRM Test Owner',
            'member',
            $faRankId,
            mentorId: $this->cfm->id,
        );

        $this->insuranceFunnelId = (int) DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $this->recruitingFunnelId = (int) DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');

        $salesProspects = $this->seedSalesProspects();
        $recruitingCandidates = $this->seedRecruitingCandidates();

        $this->seedDirectRecruits($faRankId);
        $this->seedPendingInvitations($recruitingCandidates);
        $this->seedSharedProspect($salesProspects[0] ?? null);

        app(DownlineHierarchyService::class)->rebuild();

        $this->command?->newLine();
        $this->command?->info('CRM module test data seeded.');
        $this->command?->table(
            ['Account', 'Email', 'Password', 'Use for'],
            [
                ['Primary CRM owner', 'crm-test@gwtrack.com', 'Password123', 'Sales CRM, recruiting pipeline, invites'],
                ['CFM collaborator', 'crm-test-cfm@gwtrack.com', 'Password123', 'Shared prospect access'],
                ['Direct recruit · onboarding', 'crm-recruit-onboarding@gwtrack.com', 'Password123', 'Active recruit journey'],
                ['Direct recruit · licensing', 'crm-recruit-licensing@gwtrack.com', 'Password123', 'Active recruit journey'],
                ['Direct recruit · active', 'crm-recruit-active@gwtrack.com', 'Password123', 'Active recruit journey'],
            ],
        );
        $this->command?->info('Sales CRM: /team/prospects');
        $this->command?->info('Recruiting: /team/recruiting');
    }

    private function ensureFoundation(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            RankSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);
    }

    /**
     * @return list<Prospect>
     */
    private function seedSalesProspects(): array
    {
        $scenarios = [
            ['first_name' => 'Sofia', 'last_name' => 'Sales Hot', 'email' => 'crm-test-sales-hot@example.com', 'stage_slug' => 'presentation-completed', 'interest_level' => 'hot', 'priority' => 'urgent', 'source_slug' => 'referral'],
            ['first_name' => 'Ethan', 'last_name' => 'Needs Analysis', 'email' => 'crm-test-sales-fna@example.com', 'stage_slug' => 'needs-analysis', 'interest_level' => 'warm', 'priority' => 'high', 'source_slug' => 'warm-market'],
            ['first_name' => 'Nadia', 'last_name' => 'Closed Client', 'email' => 'crm-test-sales-won@example.com', 'stage_slug' => 'closed-won', 'interest_level' => 'hot', 'priority' => 'medium', 'source_slug' => 'referral', 'converted_to' => 'client', 'is_client' => true],
            ['first_name' => 'Leo', 'last_name' => 'Overdue Followup', 'email' => 'crm-test-sales-overdue@example.com', 'stage_slug' => 'appointment-scheduled', 'interest_level' => 'warm', 'priority' => 'high', 'source_slug' => 'social-media-lead', 'overdue' => true],
            ['first_name' => 'Priya', 'last_name' => 'Dual Interest', 'email' => 'crm-test-dual-funnel@example.com', 'stage_slug' => 'needs-analysis', 'interest_level' => 'warm', 'priority' => 'medium', 'source_slug' => 'event-webinar-lead', 'funnel_type' => 'both'],
        ];

        $prospects = [];

        foreach ($scenarios as $index => $data) {
            $funnelType = $data['funnel_type'] ?? 'insurance';
            $funnelId = $funnelType === 'recruiting' ? $this->recruitingFunnelId : $this->insuranceFunnelId;
            $prospect = $this->seedProspect(array_merge($data, [
                'funnel_type' => $funnelType,
                'funnel_id' => $funnelId,
            ]));

            $this->seedProspectActivity($prospect, $index);
            $this->syncProspect($prospect);

            if ($index === 3) {
                $this->seedAppointment($prospect, 'financial-needs-analysis', now()->addDay()->setTime(15, 0));
            }

            $prospects[] = $prospect;
        }

        return $prospects;
    }

    /**
     * @return list<Prospect>
     */
    private function seedRecruitingCandidates(): array
    {
        $scenarios = [
            ['first_name' => 'Marcus', 'last_name' => 'Recruit Hot', 'email' => 'crm-test-recruit-hot@example.com', 'stage_slug' => 'training-invitation', 'interest_level' => 'hot', 'priority' => 'high', 'source_slug' => 'social-media-lead'],
            ['first_name' => 'Jordan', 'last_name' => 'Invitation Sent', 'email' => 'crm-test-recruit-invite@example.com', 'stage_slug' => 'invitation-sent', 'interest_level' => 'warm', 'priority' => 'medium', 'source_slug' => 'warm-market', 'with_invitation' => true],
            ['first_name' => 'Alex', 'last_name' => 'Presentation Done', 'email' => 'crm-test-recruit-pres@example.com', 'stage_slug' => 'business-presentation', 'interest_level' => 'hot', 'priority' => 'high', 'source_slug' => 'referral'],
            ['first_name' => 'Taylor', 'last_name' => 'Opportunity Review', 'email' => 'crm-test-recruit-review@example.com', 'stage_slug' => 'opportunity-explained', 'interest_level' => 'warm', 'priority' => 'medium', 'source_slug' => 'event-webinar-lead'],
        ];

        $candidates = [];

        foreach ($scenarios as $index => $data) {
            $candidate = $this->seedProspect(array_merge($data, [
                'funnel_type' => 'recruiting',
                'funnel_id' => $this->recruitingFunnelId,
            ]));

            $this->seedProspectActivity($candidate, $index);
            $this->syncProspect($candidate);

            if ($index === 0) {
                $this->seedAppointment($candidate, 'career-overview', now()->addDays(2)->setTime(11, 0));
            }

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    private function seedDirectRecruits(int $faRankId): void
    {
        $recruits = [
            ['email' => 'crm-recruit-onboarding@gwtrack.com', 'name' => 'CRM Recruit Onboarding', 'days_ago' => 10],
            ['email' => 'crm-recruit-licensing@gwtrack.com', 'name' => 'CRM Recruit Licensing', 'days_ago' => 45],
            ['email' => 'crm-recruit-active@gwtrack.com', 'name' => 'CRM Recruit Active', 'days_ago' => 120],
        ];

        foreach ($recruits as $recruit) {
            $user = $this->user(
                $recruit['email'],
                $recruit['name'],
                'member',
                $faRankId,
                sponsorId: $this->owner->id,
                mentorId: $this->cfm->id,
                joinedDaysAgo: $recruit['days_ago'],
            );

            $this->seedMemberProfile($user, $recruit['days_ago']);
        }
    }

    /**
     * @param  list<Prospect>  $candidates
     */
    private function seedPendingInvitations(array $candidates): void
    {
        foreach ($candidates as $candidate) {
            if (! str_contains($candidate->email ?? '', 'recruit-invite')) {
                continue;
            }

            RegistrationInvitation::query()->updateOrCreate(
                [
                    'sponsor_id' => $this->owner->id,
                    'prospect_id' => $candidate->id,
                ],
                [
                    'code' => RegistrationInvitation::generateCode(),
                    'email' => 'pending.recruit.invite@example.com',
                    'role_name' => 'member',
                    'max_uses' => 1,
                    'uses_count' => 0,
                    'expires_at' => now()->addDays(14),
                    'accepted_at' => null,
                    'revoked_at' => null,
                ],
            );
        }
    }

    private function seedSharedProspect(?Prospect $prospect): void
    {
        if (! $prospect) {
            return;
        }

        $permissionId = DB::table('prospect_share_permissions')->where('key', 'full_collaboration')->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('prospect_shares')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'shared_with' => $this->cfm->id, 'status' => 'active'],
            [
                'granted_by' => $this->owner->id,
                'prospect_share_permission_id' => $permissionId,
                'permission_level' => 'full_collaboration',
                'granted_at' => now()->subDays(2),
                'revoked_at' => null,
                'deleted_at' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedProspect(array $data): Prospect
    {
        $stageId = $this->pipelineStageId((int) $data['funnel_id'], (string) $data['stage_slug']);
        $sourceId = DB::table('prospect_sources')->where('slug', $data['source_slug'] ?? 'referral')->value('id');
        $lastContacted = now()->subDays(2)->setTime(14, 0);
        $nextFollowUp = ! empty($data['overdue'])
            ? now()->subDay()->setTime(9, 0)
            : now()->addDays(2)->setTime(10, 0);

        return Prospect::query()->updateOrCreate(
            ['owner_id' => $this->owner->id, 'email' => $data['email']],
            [
                'prospect_source_id' => $sourceId,
                'pipeline_stage_id' => $stageId,
                'funnel_type' => $data['funnel_type'],
                'prospect_funnel_id' => $data['funnel_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'preferred_name' => $data['first_name'],
                'phone' => '555-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
                'city' => 'Vancouver',
                'state_province' => 'BC',
                'country' => 'Canada',
                'timezone' => 'America/Vancouver',
                'status' => 'active',
                'interest_level' => $data['interest_level'],
                'priority' => $data['priority'],
                'last_contacted_at' => $lastContacted,
                'last_activity_at' => $lastContacted,
                'next_follow_up_at' => $nextFollowUp,
                'converted_to' => $data['converted_to'] ?? null,
                'conversion_at' => isset($data['converted_to']) ? now()->subDays(5) : null,
                'is_client' => (bool) ($data['is_client'] ?? false),
                'is_archived' => false,
                'notes_summary' => 'CRM module test prospect seeded by CrmModuleTestSeeder.',
            ],
        );
    }

    private function seedProspectActivity(Prospect $prospect, int $index): void
    {
        DB::table('prospect_activities')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'activity_type' => 'phone_call', 'occurred_at' => $prospect->last_contacted_at],
            [
                'user_id' => $this->owner->id,
                'duration_minutes' => 12 + $index,
                'outcome' => 'Connected',
                'notes' => 'Seeded CRM test activity.',
                'created_at' => $prospect->last_contacted_at,
                'updated_at' => now(),
            ],
        );

        DB::table('prospect_followups')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'assigned_user_id' => $this->owner->id, 'due_at' => $prospect->next_follow_up_at],
            [
                'followup_type' => $prospect->funnel_type === 'recruiting' ? 'training_invitation' : 'next_step',
                'priority' => $prospect->priority,
                'status' => $prospect->next_follow_up_at?->isPast() ? 'overdue' : 'pending',
                'notes' => 'Seeded follow-up for CRM module testing.',
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ],
        );
    }

    private function seedAppointment(Prospect $prospect, string $typeSlug, $scheduledAt): void
    {
        $typeId = DB::table('appointment_types')->where('slug', $typeSlug)->value('id')
            ?: DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

        if (! $typeId) {
            return;
        }

        $appointment = ProspectAppointment::query()->updateOrCreate(
            ['prospect_id' => $prospect->id, 'owner_id' => $this->owner->id, 'scheduled_at' => $scheduledAt],
            [
                'assigned_helper_id' => $this->cfm->id,
                'appointment_type_id' => $typeId,
                'timezone' => 'America/Vancouver',
                'location_or_link' => 'https://zoom.us/j/crm-module-test',
                'purpose' => $prospect->funnel_type === 'recruiting' ? 'Career overview' : 'Financial needs analysis',
                'status' => 'scheduled',
                'notes' => 'Seeded by CrmModuleTestSeeder',
                'reminder_status' => 'pending',
            ],
        );

        try {
            if (! $appointment->calendar_event_id) {
                app(ProspectCalendarBridge::class)->pushAppointment($appointment->fresh());
            }
        } catch (\Throwable) {
            // Calendar sync is optional for seeding.
        }
    }

    private function syncProspect(Prospect $prospect): void
    {
        app(CrmRelationshipExpansionService::class)->syncProspect($prospect->fresh());
        app(ProspectAiCoachService::class)->scoreProspect($prospect->fresh(['salesOpportunity', 'recruitingOpportunity']));
    }

    private function seedMemberProfile(User $user, int $joinedDaysAgo): void
    {
        $locationIds = LocationOptions::profileLocationIds('Canada', 'British Columbia', 'Canada Pacific Time');

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge([
                'phone' => '555-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'gw_associate_id' => 'GW-CRM-'.$user->id,
                'is_gw_active_associate' => true,
                'recruited_at' => now()->subDays($joinedDaysAgo)->toDateString(),
            ], $locationIds),
        );
    }

    private function pipelineStageId(int $funnelId, string $stageSlug): ?int
    {
        return DB::table('prospect_funnel_stages')
            ->where('prospect_funnel_id', $funnelId)
            ->where('slug', $stageSlug)
            ->value('pipeline_stage_id');
    }

    private function teamId(): int
    {
        return (int) Team::query()->firstOrCreate(
            ['name' => 'CRM Module Test Team'],
            [
                'description' => 'Demo team for CRM module manual testing.',
                'is_active' => true,
            ],
        )->id;
    }

    private function user(
        string $email,
        string $name,
        string $role,
        ?int $rankId,
        ?int $sponsorId = null,
        ?int $mentorId = null,
        ?int $joinedDaysAgo = null,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $this->teamId,
                'sponsor_id' => $sponsorId,
                'mentor_id' => $mentorId,
                'joined_at' => now()->subDays($joinedDaysAgo ?? 30),
                'is_active' => true,
                'is_online' => false,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
