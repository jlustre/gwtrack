<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use App\Services\Prospects\CrmRelationshipExpansionService;
use App\Services\Prospects\ProspectAiCoachService;
use App\Services\Prospects\ProspectCalendarBridge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CrmDemoSeeder extends Seeder
{
    private User $owner;

    private User $cfm;

    private User $agencyOwner;

    private User $viewer;

    private int $teamId;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $this->teamId = $this->teamId();
        $rankId = Rank::query()->where('code', 'TA')->value('id');
        $seniorRankId = Rank::query()->where('code', 'ASC')->value('id') ?: $rankId;

        $this->agencyOwner = $this->demoUser('crm-ao@gwtracks.com', 'CRM Demo Agency Owner', 'agency-owner', $seniorRankId);
        $this->cfm = $this->demoUser('crm-cfm@gwtracks.com', 'CRM Demo CFM', 'certified-field-mentor', $seniorRankId, $this->agencyOwner->id);
        $this->owner = $this->demoUser('crm-owner@gwtracks.com', 'CRM Demo Associate', 'member', $rankId, $this->agencyOwner->id, $this->cfm->id);
        $this->viewer = $this->demoUser('crm-reviewer@gwtracks.com', 'CRM Demo Shared Reviewer', 'trainer', $seniorRankId, $this->agencyOwner->id);

        $prospects = [
            $this->seedProspect([
                'email' => 'crm-sales-hot@example.com',
                'first_name' => 'Sofia',
                'last_name' => 'Martinez',
                'funnel_type' => 'insurance',
                'funnel_key' => 'insurance',
                'stage_slug' => 'presentation-completed',
                'source_slug' => 'referral',
                'interest_level' => 'hot',
                'interest_score' => 9,
                'priority' => 'urgent',
                'next_follow_up_at' => now()->subDay()->setTime(9, 0),
                'last_contacted_at' => now()->subDays(2)->setTime(14, 30),
                'qualification_traits' => ['age_25_plus' => true, 'married' => true, 'has_mortgage' => true, 'has_dependent_kids' => true],
                'notes_summary' => 'High-intent sales prospect. Presentation is complete and an overdue follow-up should trigger AI next-best-action guidance.',
                'campaign_name' => 'CRM Phase 8 Demo',
                'referral_source_name' => 'Existing client referral',
            ]),
            $this->seedProspect([
                'email' => 'crm-recruiting-hot@example.com',
                'first_name' => 'Marcus',
                'last_name' => 'Chen',
                'funnel_type' => 'recruiting',
                'funnel_key' => 'recruiting',
                'stage_slug' => 'training-invitation',
                'source_slug' => 'social-media-lead',
                'interest_level' => 'hot',
                'interest_score' => 10,
                'priority' => 'high',
                'next_follow_up_at' => now()->addDay()->setTime(11, 0),
                'last_contacted_at' => now()->subDay()->setTime(16, 0),
                'qualification_traits' => ['business_minded' => true, 'employed_full_time' => true, 'legal_resident' => true],
                'notes_summary' => 'Strong recruiting candidate with business-owner mindset and active interest in training.',
                'campaign_name' => 'Career Opportunity Webinar',
                'referral_source_name' => 'Instagram campaign',
            ]),
            $this->seedProspect([
                'email' => 'crm-dual-funnel@example.com',
                'first_name' => 'Priya',
                'last_name' => 'Raman',
                'funnel_type' => 'both',
                'funnel_key' => 'insurance',
                'stage_slug' => 'needs-analysis',
                'source_slug' => 'event-webinar-lead',
                'interest_level' => 'warm',
                'interest_score' => 7,
                'priority' => 'medium',
                'next_follow_up_at' => now()->addDays(2)->setTime(13, 0),
                'last_contacted_at' => now()->subDays(4)->setTime(10, 0),
                'qualification_traits' => ['age_25_plus' => true, 'legal_resident' => true, 'homeowner' => true],
                'notes_summary' => 'Dual-funnel relationship. Interested in coverage now and business partnership later.',
                'campaign_name' => 'Protection and Opportunity Night',
                'referral_source_name' => 'Webinar attendee',
            ]),
            $this->seedProspect([
                'email' => 'crm-no-show@example.com',
                'first_name' => 'Ethan',
                'last_name' => 'Brooks',
                'funnel_type' => 'insurance',
                'funnel_key' => 'insurance',
                'stage_slug' => 'appointment-scheduled',
                'source_slug' => 'warm-market',
                'interest_level' => 'warm',
                'interest_score' => 6,
                'priority' => 'high',
                'next_follow_up_at' => now()->subDays(2)->setTime(15, 0),
                'last_contacted_at' => now()->subDays(9)->setTime(15, 45),
                'qualification_traits' => ['age_25_plus' => true, 'has_dependent_kids' => true],
                'notes_summary' => 'Missed appointment demo record for no-show, overdue follow-up, and reschedule review workflows.',
                'campaign_name' => 'Warm Market Outreach',
                'referral_source_name' => 'Personal contact',
            ]),
            $this->seedProspect([
                'email' => 'crm-converted-client@example.com',
                'first_name' => 'Nadia',
                'last_name' => 'King',
                'funnel_type' => 'insurance',
                'funnel_key' => 'insurance',
                'stage_slug' => 'closed-won',
                'source_slug' => 'referral',
                'interest_level' => 'hot',
                'interest_score' => 8,
                'priority' => 'medium',
                'next_follow_up_at' => now()->addDays(21)->setTime(10, 0),
                'last_contacted_at' => now()->subDays(3)->setTime(12, 0),
                'converted_to' => 'client',
                'conversion_at' => now()->subDays(5),
                'is_client' => true,
                'qualification_traits' => ['age_25_plus' => true, 'married' => true, 'homeowner' => true],
                'notes_summary' => 'Converted client record for closed-won reporting and referral-request follow-up.',
                'campaign_name' => 'Referral Campaign',
                'referral_source_name' => 'Sofia Martinez',
            ]),
        ];

        foreach ($prospects as $index => $prospect) {
            $this->seedTimeline($prospect, $index);
            $this->seedFollowUps($prospect, $index);
            $this->seedAppointments($prospect, $index);
            app(CrmRelationshipExpansionService::class)->syncProspect($prospect->fresh());
            app(ProspectAiCoachService::class)->scoreProspect($prospect->fresh(['salesOpportunity', 'recruitingOpportunity']));
        }

        $this->seedGovernanceSignals($prospects[0], $prospects[1]);
        $this->seedReferral($prospects[2], $prospects[0]);
        $this->seedImportSummary();
    }

    private function seedProspect(array $data): Prospect
    {
        $funnelId = $this->funnelId($data['funnel_key']);
        $stageId = $this->pipelineStageId($funnelId, $data['stage_slug']);
        $sourceId = DB::table('prospect_sources')->where('slug', $data['source_slug'])->value('id');

        return Prospect::query()->updateOrCreate(
            ['owner_id' => $this->owner->id, 'email' => $data['email']],
            [
                'prospect_source_id' => $sourceId,
                'pipeline_stage_id' => $stageId,
                'funnel_type' => $data['funnel_type'],
                'prospect_funnel_id' => $funnelId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'preferred_name' => $data['first_name'],
                'phone' => '555-'.fake()->unique()->numerify('####'),
                'city' => 'Vancouver',
                'state_province' => 'BC',
                'country' => 'Canada',
                'timezone' => 'America/Vancouver',
                'preferred_language' => 'English',
                'occupation' => $data['funnel_type'] === 'recruiting' ? 'Business Development Manager' : 'Homeowner',
                'employer_business' => $data['funnel_type'] === 'recruiting' ? 'Independent Contractor' : 'Metro Health Group',
                'date_of_birth' => now()->subYears(34)->toDateString(),
                'gender' => 'prefer_not_to_say',
                'marital_status' => 'married',
                'children_count' => 2,
                'dependents' => [['name' => 'Demo Child', 'age' => 8]],
                'status' => 'active',
                'interest_level' => $data['interest_level'],
                'interest_score' => $data['interest_score'],
                'priority' => $data['priority'],
                'fna_status' => $data['funnel_type'] === 'recruiting' ? null : 'not_started',
                'visibility_preset' => 'private',
                'referral_source_name' => $data['referral_source_name'],
                'campaign_name' => $data['campaign_name'],
                'social_source' => $data['source_slug'] === 'social-media-lead' ? 'Instagram' : null,
                'next_follow_up_at' => $data['next_follow_up_at'],
                'last_contacted_at' => $data['last_contacted_at'],
                'last_activity_at' => $data['last_contacted_at'],
                'appointment_at' => now()->addDays(2)->setTime(15, 0),
                'conversion_at' => $data['conversion_at'] ?? null,
                'converted_to' => $data['converted_to'] ?? null,
                'notes_summary' => $data['notes_summary'],
                'qualification_traits' => $data['qualification_traits'],
                'qualification_notes' => 'Seeded qualification profile for CRM demo testing.',
                'is_client' => (bool) ($data['is_client'] ?? false),
                'is_archived' => false,
                'archived_at' => null,
            ]
        );
    }

    private function seedTimeline(Prospect $prospect, int $index): void
    {
        DB::table('prospect_notes')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'user_id' => $this->owner->id, 'note' => 'CRM demo note: relationship context, goals, objections, and next steps are documented here.'],
            ['is_private' => false, 'deleted_at' => null, 'created_at' => now()->subDays(5), 'updated_at' => now()]
        );

        DB::table('prospect_activities')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'activity_type' => 'phone_call', 'occurred_at' => $prospect->last_contacted_at],
            [
                'user_id' => $this->owner->id,
                'duration_minutes' => 18 + $index,
                'outcome' => $index === 3 ? 'No answer' : 'Connected',
                'notes' => 'Seeded activity for the unified CRM timeline.',
                'next_action' => 'Confirm next meeting and prepare follow-up.',
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'metadata' => json_encode(['source' => self::class]),
                'deleted_at' => null,
                'created_at' => $prospect->last_contacted_at,
                'updated_at' => now(),
            ]
        );

        DB::table('prospect_communications')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'user_id' => $this->owner->id, 'contacted_at' => $prospect->last_contacted_at],
            [
                'communication_type_id' => DB::table('communication_types')->where('slug', $index % 2 === 0 ? 'text-message' : 'call')->value('id'),
                'direction' => 'outbound',
                'outcome' => $index === 3 ? 'Left voicemail' : 'Connected',
                'notes' => 'Seeded CRM communication history for AI conversation summary.',
                'next_action' => 'Send recap and confirm the next calendar commitment.',
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'duration_minutes' => 8 + $index,
                'deleted_at' => null,
                'created_at' => $prospect->last_contacted_at,
                'updated_at' => now(),
            ]
        );

        DB::table('prospect_stage_history')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'to_stage_id' => $prospect->pipeline_stage_id, 'change_source' => 'crm_demo_seed'],
            [
                'from_stage_id' => null,
                'from_funnel_id' => null,
                'to_funnel_id' => $prospect->prospect_funnel_id,
                'changed_by' => $this->owner->id,
                'metadata' => json_encode(['note' => 'Seeded phase demo stage history.']),
                'created_at' => now()->subDays(6),
            ]
        );
    }

    private function seedFollowUps(Prospect $prospect, int $index): void
    {
        DB::table('prospect_followups')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'assigned_user_id' => $this->owner->id, 'due_at' => $prospect->next_follow_up_at],
            [
                'followup_type' => $index === 1 ? 'training_invitation' : 'next_step',
                'priority' => $prospect->priority,
                'status' => $prospect->next_follow_up_at?->isPast() ? 'overdue' : 'pending',
                'notes' => 'Seeded CRM follow-up. Overdue items should surface in tasks, reports, and AI next-best-action.',
                'completed_at' => null,
                'deleted_at' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now(),
            ]
        );
    }

    private function seedAppointments(Prospect $prospect, int $index): void
    {
        $typeSlug = $prospect->funnel_type === 'recruiting' ? 'career-overview' : 'financial-needs-analysis';
        $typeId = DB::table('appointment_types')->where('slug', $typeSlug)->value('id')
            ?: DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

        $appointment = ProspectAppointment::query()->updateOrCreate(
            ['prospect_id' => $prospect->id, 'owner_id' => $this->owner->id, 'scheduled_at' => $index === 3 ? now()->subDays(1)->setTime(16, 0) : now()->addDays($index + 1)->setTime(15, 0)],
            [
                'assigned_helper_id' => $index % 2 === 0 ? $this->cfm->id : $this->agencyOwner->id,
                'appointment_type_id' => $typeId,
                'timezone' => 'America/Vancouver',
                'location_or_link' => 'https://zoom.us/j/crm-demo-'.$index,
                'purpose' => $prospect->funnel_type === 'recruiting' ? 'Career overview and opportunity discussion' : 'Needs analysis and protection review',
                'status' => $index === 3 ? 'no_show' : 'scheduled',
                'notes' => 'Seeded appointment for Calendar-first CRM testing.',
                'reminder_status' => 'pending',
                'deleted_at' => null,
            ]
        );

        if ($appointment->status === 'scheduled') {
            app(ProspectCalendarBridge::class)->pushAppointment($appointment->fresh());
        }
    }

    private function seedGovernanceSignals(Prospect $salesProspect, Prospect $recruitingProspect): void
    {
        $permissionId = DB::table('prospect_share_permissions')->where('key', 'full_collaboration')->value('id');

        DB::table('prospect_shares')->updateOrInsert(
            ['prospect_id' => $salesProspect->id, 'shared_with' => $this->cfm->id, 'permission_level' => 'full_collaboration', 'status' => 'active'],
            [
                'granted_by' => $this->owner->id,
                'prospect_share_permission_id' => $permissionId,
                'granted_at' => now()->subDays(2),
                'expires_at' => now()->addDays(3),
                'revoked_at' => null,
                'notes' => 'Expiring demo share for Phase 8 governance review.',
                'deleted_at' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]
        );

        DB::table('prospect_shares')->updateOrInsert(
            ['prospect_id' => $recruitingProspect->id, 'shared_with' => $this->viewer->id, 'permission_level' => 'full_collaboration', 'status' => 'active'],
            [
                'granted_by' => $this->owner->id,
                'prospect_share_permission_id' => $permissionId,
                'granted_at' => now()->subDays(45),
                'expires_at' => null,
                'revoked_at' => null,
                'notes' => 'Open-ended demo share for Phase 8 governance review.',
                'deleted_at' => null,
                'created_at' => now()->subDays(45),
                'updated_at' => now(),
            ]
        );

        DB::table('prospect_access_logs')->updateOrInsert(
            ['prospect_id' => $salesProspect->id, 'actor_id' => $this->viewer->id, 'action' => 'view'],
            [
                'subject_user_id' => $this->owner->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'CRM demo seeder',
                'metadata' => json_encode(['source' => self::class]),
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ]
        );

        $eventTypeId = DB::table('calendar_event_types')->where('slug', 'recruiting-presentation')->value('id')
            ?: DB::table('calendar_event_types')->where('slug', 'prospect-appointment')->value('id');
        $categoryId = DB::table('calendar_categories')->where('slug', 'prospects')->value('id');

        CalendarEvent::query()->updateOrCreate(
            ['organizer_id' => $this->owner->id, 'title' => 'Team Visible CRM Review: '.$recruitingProspect->displayName()],
            [
                'calendar_event_type_id' => $eventTypeId,
                'calendar_category_id' => $categoryId,
                'description' => 'Seeded team-visible CRM calendar event for Phase 8 governance exposure review.',
                'starts_at' => now()->addDays(4)->setTime(11, 0),
                'ends_at' => now()->addDays(4)->setTime(12, 0),
                'timezone' => 'America/Vancouver',
                'is_all_day' => false,
                'is_recurring' => false,
                'location' => null,
                'meeting_link' => 'https://zoom.us/j/crm-governance-demo',
                'visibility' => 'team',
                'status' => 'scheduled',
                'color' => '#7C3AED',
                'related_prospect_id' => $recruitingProspect->id,
                'related_contact_id' => $recruitingProspect->contact_id,
                'related_sales_opportunity_id' => $recruitingProspect->sales_opportunity_id,
                'related_recruiting_opportunity_id' => $recruitingProspect->recruiting_opportunity_id,
                'notes' => 'This event should appear in the Access Manager Calendar Exposure panel.',
                'deleted_at' => null,
            ]
        );
    }

    private function seedReferral(Prospect $referred, Prospect $referrer): void
    {
        $referred->refresh();
        $referrer->refresh();

        DB::table('contact_referrals')->updateOrInsert(
            ['owner_id' => $this->owner->id, 'referrer_prospect_id' => $referrer->id, 'referred_prospect_id' => $referred->id],
            [
                'referrer_contact_id' => $referrer->contact_id,
                'referrer_user_id' => null,
                'referred_contact_id' => $referred->contact_id,
                'referral_type' => 'prospect',
                'status' => 'new',
                'source_label' => 'CRM demo referral tree',
                'notes' => 'Seeded referral connecting sales and dual-funnel relationships.',
                'referred_at' => now()->subDays(3),
                'accepted_at' => null,
                'converted_at' => null,
                'metadata' => json_encode(['source' => self::class]),
                'deleted_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ]
        );
    }

    private function seedImportSummary(): void
    {
        DB::table('prospect_imports')->updateOrInsert(
            ['user_id' => $this->owner->id, 'file_name' => 'crm-phase-demo-import.csv'],
            [
                'status' => 'completed',
                'total_rows' => 5,
                'imported_rows' => 5,
                'skipped_rows' => 0,
                'duplicate_rows' => 0,
                'preview_payload' => json_encode(['columns' => ['first_name', 'last_name', 'email', 'funnel_type', 'stage']]),
                'duplicate_payload' => json_encode([]),
                'completed_at' => now()->subHours(6),
                'deleted_at' => null,
                'created_at' => now()->subHours(6),
                'updated_at' => now(),
            ]
        );
    }

    private function funnelId(string $key): int
    {
        return (int) DB::table('prospect_funnels')->where('key', $key)->value('id');
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
        $team = Team::query()->firstOrCreate(
            ['name' => 'CRM Demo Team'],
            [
                'description' => 'Demo team for CRM/Funnel/Calendar test data.',
                'is_active' => true,
            ]
        );

        return $team->id;
    }

    private function demoUser(string $email, string $name, string $role, ?int $rankId, ?int $sponsorId = null, ?int $mentorId = null): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $this->teamId,
                'sponsor_id' => $sponsorId,
                'mentor_id' => $mentorId,
                'joined_at' => now()->subMonths(2),
                'is_active' => true,
                'is_online' => false,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
