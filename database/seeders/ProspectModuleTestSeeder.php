<?php

namespace Database\Seeders;

use App\Models\Prospect;
use App\Models\ProspectGoal;
use App\Models\Rank;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Prospects\ProspectCalendarBridge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * Curated Prospect Management QA data for manual testing (Phases 1–7).
 *
 * Run after foundation seeders:
 *   php artisan db:seed --class=ProspectModuleTestSeeder
 *
 * Or full stack:
 *   php artisan db:seed
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ PRIMARY LOGIN (use this for most tests)                                 │
 * │   Email:    prospects@gwtrack.com                                      │
 * │   Password: Password123                                                 │
 * │   Role:     member · owns curated prospects below                       │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │ COLLABORATOR LOGINS                                                     │
 * │   prospect-cfm@gwtrack.com      / Password123  (CFM · shared prospects)│
 * │   prospect-sponsor@gwtrack.com  / Password123  (team-leader · sponsor) │
 * │   prospect-recruit@gwtrack.com  / Password123  (direct recruit)        │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * Named prospects (search on dashboard):
 *   Kanban Test Hot · Stalled Hot Lead · Presentation Maria · Application Alex
 *   Recruit Candidate Sam · Client Ready Chris · Convert Associate Dana
 *   Overdue Followup Frank · Upcoming Appt Grace · Import Duplicate Test
 */
class ProspectModuleTestSeeder extends Seeder
{
    private User $owner;

    private User $cfm;

    private User $sponsor;

    private User $recruit;

    private int $insuranceFunnelId;

    private int $recruitingFunnelId;

    /** @var array<string, int> */
    private array $stageIds = [];

    public function run(): void
    {
        $this->ensureFoundation();

        $teamId = (int) DB::table('teams')->where('name', 'Wealth Legacy Alliance')->value('id');
        $faRankId = Rank::where('code', 'TA')->value('id');
        $sfaRankId = Rank::where('code', 'ASC')->value('id');

        $this->sponsor = $this->user('prospect-sponsor@gwtrack.com', 'Prospect QA Sponsor', 'team-leader', $sfaRankId, $teamId);
        $this->owner = $this->user('prospects@gwtrack.com', 'Prospect Demo Owner', 'member', $faRankId, $teamId, $this->sponsor->id);
        $this->cfm = $this->user('prospect-cfm@gwtrack.com', 'Prospect CFM Coach', 'certified-field-mentor', $sfaRankId, $teamId, $this->sponsor->id);
        $this->recruit = $this->user('prospect-recruit@gwtrack.com', 'Prospect QA Recruit', 'member', $faRankId, $teamId, $this->owner->id);

        $this->owner->forceFill(['mentor_id' => $this->cfm->id])->save();

        app(DownlineHierarchyService::class)->rebuild();

        $this->insuranceFunnelId = (int) DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $this->recruitingFunnelId = (int) DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');
        $this->stageIds = DB::table('pipeline_stages')
            ->whereNull('user_id')
            ->pluck('id', 'slug')
            ->all();

        $sourceId = (int) DB::table('prospect_sources')->where('name', 'Social Media Lead')->value('id');
        $sharePermissionId = (int) DB::table('prospect_share_permissions')->where('key', 'full_collaboration')->value('id');
        $appointmentTypeId = (int) DB::table('appointment_types')->value('id');

        $this->seedCuratedProspects($sourceId, $sharePermissionId, $appointmentTypeId);
        $this->seedActivityLogSummaryData();
        $this->seedGoals();
        $this->seedSampleImportCsv();
        $this->seedImportHistory();

        $this->command?->newLine();
        $this->command?->info('Prospect Module QA data seeded.');
        $this->command?->table(
            ['Account', 'Email', 'Password', 'Use for'],
            [
                ['Primary owner', 'prospects@gwtrack.com', 'Password123', 'Dashboard, pipeline, convert, import/export, analytics'],
                ['CFM collaborator', 'prospect-cfm@gwtrack.com', 'Password123', 'Shared With Me, shared profile access'],
                ['Sponsor / manager', 'prospect-sponsor@gwtrack.com', 'Password123', 'Team analytics aggregate (downline)'],
                ['Direct recruit', 'prospect-recruit@gwtrack.com', 'Password123', 'Team visibility preset target'],
            ]
        );
        $this->command?->info('Start at: /team/prospects');
    }

    private function ensureFoundation(): void
    {
        $required = [
            'prospect_sources' => ProspectLookupSeeder::class,
            'prospect_funnels' => ProspectFunnelSeeder::class,
        ];

        foreach ($required as $table => $seeder) {
            if (! DB::table($table)->exists()) {
                $this->call($seeder);
            }
        }

        if (! DB::table('roles')->where('name', 'member')->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        if (! Rank::where('code', 'TA')->exists()) {
            $this->call(RankSeeder::class);
        }
    }

    private function seedCuratedProspects(int $sourceId, int $sharePermissionId, int $appointmentTypeId): void
    {
        $scenarios = [
            [
                'first_name' => 'Kanban',
                'last_name' => 'Test Hot',
                'email' => 'kanban.test.hot@example.com',
                'phone' => '555-0101',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'new-lead',
                'interest_level' => 'hot',
                'priority' => 'high',
                'last_contacted_at' => now()->subDays(1),
                'next_follow_up_at' => now()->addDay(),
            ],
            [
                'first_name' => 'Stalled',
                'last_name' => 'Hot Lead',
                'email' => 'stalled.hot.lead@example.com',
                'phone' => '555-0102',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'contacted',
                'interest_level' => 'hot',
                'priority' => 'urgent',
                'last_contacted_at' => now()->subDays(10),
                'next_follow_up_at' => now()->subDays(2),
            ],
            [
                'first_name' => 'Presentation',
                'last_name' => 'Maria',
                'email' => 'presentation.maria@example.com',
                'phone' => '555-0103',
                'funnel_type' => 'recruiting',
                'funnel_id' => $this->recruitingFunnelId,
                'stage_slug' => 'presentation-completed',
                'interest_level' => 'warm',
                'priority' => 'high',
                'last_contacted_at' => now()->subDays(4),
            ],
            [
                'first_name' => 'Application',
                'last_name' => 'Alex',
                'email' => 'application.alex@example.com',
                'phone' => '555-0104',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'application-submitted',
                'interest_level' => 'hot',
                'priority' => 'high',
                'last_contacted_at' => now()->subDays(3),
            ],
            [
                'first_name' => 'Recruit',
                'last_name' => 'Candidate Sam',
                'email' => 'recruit.sam@example.com',
                'phone' => '555-0105',
                'funnel_type' => 'recruiting',
                'funnel_id' => $this->recruitingFunnelId,
                'stage_slug' => 'invitation-sent',
                'interest_level' => 'warm',
                'priority' => 'medium',
                'last_contacted_at' => now()->subDays(2),
            ],
            [
                'first_name' => 'Client',
                'last_name' => 'Ready Chris',
                'email' => 'client.chris@example.com',
                'phone' => '555-0106',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'solution-presented',
                'interest_level' => 'hot',
                'priority' => 'high',
                'last_contacted_at' => now()->subDay(),
            ],
            [
                'first_name' => 'Convert',
                'last_name' => 'Associate Dana',
                'email' => 'convert.dana@example.com',
                'phone' => '555-0107',
                'funnel_type' => 'recruiting',
                'funnel_id' => $this->recruitingFunnelId,
                'stage_slug' => 'opportunity-review',
                'interest_level' => 'hot',
                'priority' => 'high',
                'last_contacted_at' => now()->subDay(),
            ],
            [
                'first_name' => 'Overdue',
                'last_name' => 'Followup Frank',
                'email' => 'overdue.frank@example.com',
                'phone' => '555-0108',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'discovery-call',
                'interest_level' => 'warm',
                'priority' => 'high',
                'last_contacted_at' => now()->subDays(5),
                'next_follow_up_at' => now()->subDay(),
            ],
            [
                'first_name' => 'Upcoming',
                'last_name' => 'Appt Grace',
                'email' => 'upcoming.grace@example.com',
                'phone' => '555-0109',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'financial-review',
                'interest_level' => 'hot',
                'priority' => 'medium',
                'last_contacted_at' => now()->subDays(1),
                'appointment_at' => now()->addDay()->setTime(14, 0),
            ],
            [
                'first_name' => 'Import',
                'last_name' => 'Duplicate Test',
                'email' => 'import.duplicate@example.com',
                'phone' => '555-0199',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'new-lead',
                'interest_level' => 'cold',
                'priority' => 'low',
                'last_contacted_at' => now()->subDays(30),
            ],
            [
                'first_name' => 'Shared',
                'last_name' => 'With CFM',
                'email' => 'shared.cfm@example.com',
                'phone' => '555-0110',
                'funnel_type' => 'insurance',
                'funnel_id' => $this->insuranceFunnelId,
                'stage_slug' => 'contact-attempted',
                'interest_level' => 'warm',
                'priority' => 'medium',
                'last_contacted_at' => now()->subDays(2),
                'share_with_cfm' => true,
            ],
        ];

        foreach ($scenarios as $data) {
            $stageId = $this->stageIds[$data['stage_slug']] ?? null;

            $prospect = Prospect::updateOrCreate(
                ['owner_id' => $this->owner->id, 'email' => $data['email']],
                [
                    'prospect_source_id' => $sourceId,
                    'prospect_funnel_id' => $data['funnel_id'],
                    'pipeline_stage_id' => $stageId,
                    'funnel_type' => $data['funnel_type'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'preferred_name' => $data['first_name'],
                    'phone' => $data['phone'],
                    'city' => 'Vancouver',
                    'state_province' => 'BC',
                    'country' => 'Canada',
                    'timezone' => 'America/Vancouver',
                    'status' => 'active',
                    'interest_level' => $data['interest_level'],
                    'priority' => $data['priority'],
                    'last_contacted_at' => $data['last_contacted_at'] ?? now()->subDays(3),
                    'last_activity_at' => now(),
                    'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                    'appointment_at' => $data['appointment_at'] ?? null,
                    'is_archived' => false,
                    'is_client' => false,
                    'notes_summary' => 'QA scenario prospect for manual module testing.',
                ]
            );

            $this->seedActivity($prospect, $data['last_contacted_at'] ?? now()->subDays(3));
            $this->seedStageHistory($prospect, $stageId);

            if (! empty($data['share_with_cfm'])) {
                $this->share($prospect, $sharePermissionId);
            }

            if (isset($data['next_follow_up_at'])) {
                $this->followUp($prospect, $data['next_follow_up_at'], $data['priority']);
            }

            if (isset($data['appointment_at'])) {
                $this->appointment($prospect, $appointmentTypeId, $data['appointment_at']);
            }
        }
    }

    private function seedActivityLogSummaryData(): void
    {
        $callTypeId = (int) DB::table('communication_types')->where('name', 'Call')->value('id');
        $prospects = Prospect::query()->where('owner_id', $this->owner->id)->get()->keyBy('email');

        $kanban = $prospects->get('kanban.test.hot@example.com');
        $maria = $prospects->get('presentation.maria@example.com');
        $sam = $prospects->get('recruit.sam@example.com');
        $grace = $prospects->get('upcoming.grace@example.com');
        $dana = $prospects->get('convert.dana@example.com');
        $chris = $prospects->get('client.chris@example.com');

        if ($kanban) {
            $this->seedSummaryActivity($kanban->id, 'phone_call', now()->subDays(2), 'No answer');
            $this->seedSummaryActivity($kanban->id, 'phone_call', now()->subDay(), 'Connected');
            if ($callTypeId) {
                $this->seedSummaryCommunication($kanban->id, $callTypeId, now(), 'Left voicemail');
            }
        }

        if ($maria) {
            $this->seedSummaryActivity($maria->id, 'zoom_meeting', now()->subDays(3), 'Completed');
            $this->seedSummaryActivity($maria->id, 'presentation', now()->subDay(), 'Attended');
            $this->seedSummaryStageHistory($maria, $this->stageIds['presentation-completed'] ?? null, now()->subDay());
        }

        if ($sam) {
            $this->seedSummaryStageHistory($sam, $this->stageIds['invitation-sent'] ?? null, now()->subDays(2));
        }

        if ($grace) {
            $grace->update(['fna_status' => 'completed', 'updated_at' => now()->subDay()]);
            $this->seedSummaryActivity($grace->id, 'financial_review', now()->subDay(), 'Completed');
            $this->seedSummaryStageHistory($grace, $this->stageIds['financial-review'] ?? null, now()->subDays(3));
        }

        if ($dana) {
            $this->seedSummaryStageHistory($dana, $this->stageIds['became-associate'] ?? null, now()->subDays(1));
            DB::table('prospect_conversions')->updateOrInsert(
                ['prospect_id' => $dana->id, 'conversion_type' => 'associate'],
                [
                    'converted_by' => $this->owner->id,
                    'converted_at' => now()->subDay(),
                    'notes' => 'QA associate conversion for activity summary.',
                    'created_at' => now()->subDay(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($chris) {
            $this->seedSummaryStageHistory($chris, $this->stageIds['became-client'] ?? null, now());
            DB::table('prospect_conversions')->updateOrInsert(
                ['prospect_id' => $chris->id, 'conversion_type' => 'client'],
                [
                    'converted_by' => $this->owner->id,
                    'converted_at' => now(),
                    'notes' => 'QA client conversion for activity summary.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedSummaryActivity(string $prospectId, string $type, $occurredAt, string $outcome): void
    {
        DB::table('prospect_activities')->insert([
            'prospect_id' => $prospectId,
            'user_id' => $this->owner->id,
            'activity_type' => $type,
            'outcome' => $outcome,
            'notes' => 'Activity log summary QA seed.',
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
            'updated_at' => now(),
        ]);
    }

    private function seedSummaryCommunication(string $prospectId, int $callTypeId, $contactedAt, string $outcome): void
    {
        DB::table('prospect_communications')->insert([
            'prospect_id' => $prospectId,
            'user_id' => $this->owner->id,
            'communication_type_id' => $callTypeId,
            'direction' => 'outbound',
            'outcome' => $outcome,
            'notes' => 'Activity log summary QA seed.',
            'contacted_at' => $contactedAt,
            'created_at' => $contactedAt,
            'updated_at' => now(),
        ]);
    }

    private function seedSummaryStageHistory(Prospect $prospect, ?int $stageId, $createdAt): void
    {
        if (! $stageId) {
            return;
        }

        DB::table('prospect_stage_history')->insert([
            'prospect_id' => $prospect->id,
            'from_stage_id' => null,
            'to_stage_id' => $stageId,
            'from_funnel_id' => $prospect->prospect_funnel_id,
            'to_funnel_id' => $prospect->prospect_funnel_id,
            'changed_by' => $this->owner->id,
            'change_source' => 'qa_seed',
            'created_at' => $createdAt,
        ]);
    }

    private function seedGoals(): void
    {
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();

        foreach ([
            ['metric_key' => 'contacts', 'target_value' => 20, 'actual_value' => 8],
            ['metric_key' => 'appointments', 'target_value' => 10, 'actual_value' => 3],
            ['metric_key' => 'new_prospects', 'target_value' => 15, 'actual_value' => 11],
        ] as $goal) {
            ProspectGoal::updateOrCreate(
                [
                    'user_id' => $this->owner->id,
                    'period_type' => 'monthly',
                    'period_start' => $periodStart,
                    'metric_key' => $goal['metric_key'],
                ],
                [
                    'period_end' => $periodEnd,
                    'target_value' => $goal['target_value'],
                    'actual_value' => $goal['actual_value'],
                ]
            );
        }
    }

    private function seedSampleImportCsv(): void
    {
        $path = storage_path('app/demo/prospect-import-sample.csv');
        File::ensureDirectoryExists(dirname($path));

        $csv = <<<'CSV'
first_name,last_name,email,phone,city,funnel_type
Import,New One,new.import.one@example.com,555-0201,Calgary,insurance
Import,New Two,new.import.two@example.com,555-0202,Toronto,recruiting
Import,Duplicate Test,duplicate.attempt@example.com,555-0199,Edmonton,insurance
CSV;

        File::put($path, $csv);
    }

    private function seedImportHistory(): void
    {
        DB::table('prospect_imports')->updateOrInsert(
            ['user_id' => $this->owner->id, 'file_name' => 'prospect-import-sample.csv'],
            [
                'status' => 'completed',
                'total_rows' => 3,
                'imported_rows' => 2,
                'skipped_rows' => 0,
                'duplicate_rows' => 1,
                'preview_payload' => json_encode(['columns' => ['first_name', 'last_name', 'email', 'phone', 'city', 'funnel_type']]),
                'duplicate_payload' => json_encode(['duplicates' => [['email' => 'import.duplicate@example.com', 'phone' => '555-0199']]]),
                'completed_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]
        );
    }

    private function user(string $email, string $name, string $role, ?int $rankId, int $teamId, ?int $sponsorId = null): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $teamId,
                'sponsor_id' => $sponsorId,
                'is_active' => true,
                'joined_at' => now()->subMonths(6),
                'is_online' => false,
            ]
        );

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);

        return $user;
    }

    private function seedActivity(Prospect $prospect, $occurredAt): void
    {
        DB::table('prospect_activities')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'activity_type' => 'phone_call', 'occurred_at' => $occurredAt],
            [
                'user_id' => $this->owner->id,
                'outcome' => 'Connected',
                'notes' => 'QA seeded activity for timeline testing.',
                'created_at' => $occurredAt,
                'updated_at' => now(),
            ]
        );
    }

    private function seedStageHistory(Prospect $prospect, ?int $stageId): void
    {
        if (! $stageId) {
            return;
        }

        DB::table('prospect_stage_history')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'to_stage_id' => $stageId, 'change_source' => 'create'],
            [
                'from_stage_id' => null,
                'from_funnel_id' => $prospect->prospect_funnel_id,
                'to_funnel_id' => $prospect->prospect_funnel_id,
                'changed_by' => $this->owner->id,
                'created_at' => now()->subDays(7),
            ]
        );
    }

    private function followUp(Prospect $prospect, $dueAt, string $priority): void
    {
        DB::table('prospect_followups')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'assigned_user_id' => $this->owner->id, 'followup_type' => 'qa_manual_test'],
            [
                'due_at' => $dueAt,
                'priority' => $priority,
                'status' => $dueAt->isPast() ? 'overdue' : 'pending',
                'notes' => 'QA follow-up for Follow-Up Center testing.',
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]
        );
    }

    private function appointment(Prospect $prospect, int $typeId, $scheduledAt): void
    {
        DB::table('prospect_appointments')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'owner_id' => $this->owner->id, 'scheduled_at' => $scheduledAt],
            [
                'assigned_helper_id' => $this->cfm->id,
                'appointment_type_id' => $typeId,
                'timezone' => 'America/Vancouver',
                'location_or_link' => 'https://zoom.us/j/qa-grace-appt',
                'purpose' => 'Financial review — QA test appointment',
                'status' => 'scheduled',
                'notes' => 'Created by ProspectModuleTestSeeder',
                'reminder_status' => 'pending',
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ]
        );

        if (class_exists(ProspectCalendarBridge::class)) {
            $appointment = \App\Models\ProspectAppointment::query()
                ->where('prospect_id', $prospect->id)
                ->where('scheduled_at', $scheduledAt)
                ->first();

            if ($appointment && ! $appointment->calendar_event_id) {
                try {
                    app(ProspectCalendarBridge::class)->pushAppointment($appointment);
                } catch (\Throwable) {
                    // Calendar types may be missing if CalendarModuleSeeder has not run.
                }
            }
        }
    }

    private function share(Prospect $prospect, int $permissionId): void
    {
        DB::table('prospect_shares')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'shared_with' => $this->cfm->id, 'status' => 'active'],
            [
                'granted_by' => $this->owner->id,
                'prospect_share_permission_id' => $permissionId,
                'permission_level' => 'full_collaboration',
                'granted_at' => now()->subDays(3),
                'revoked_at' => null,
                'deleted_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ]
        );
    }
}
