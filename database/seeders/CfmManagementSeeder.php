<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\CalendarCategory;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\CfmAdvancementGuideline;
use App\Models\CfmMentorProfile;
use App\Models\CfmRankTier;
use App\Models\CfmRecommendationSuggestion;
use App\Models\MentorAssignment;
use App\Models\Rank;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\TaskLibrary;
use App\Support\LocationOptions;
use App\Services\DownlineHierarchyService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CfmManagementSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $this->seedRankStructure();

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->first();
        $celeste = User::where('email', 'cfm@gwtrack.com')->first();

        if (! $agencyOwner || ! $celeste) {
            $this->command?->warn('CfmManagementSeeder skipped: run TaskScenarioSeeder first.');

            return;
        }

        $teamId = $agencyOwner->team_id;
        $rankSfa = Rank::where('code', 'ASC')->value('id');
        $rankFa = Rank::where('code', 'TA')->value('id');
        $categoryId = $this->calendarCategoryId();

        $maria = $this->cfmUser(
            'maria.cfm@gwtrack.com',
            'Maria Santos',
            $teamId,
            $rankSfa,
            $agencyOwner->id,
            [
                'country' => 'Canada',
                'province' => 'Ontario',
                'city' => 'Toronto',
                'timezone' => 'Canada Eastern Time',
                'phone' => '555-0101',
            ]
        );

        $john = $this->cfmUser(
            'john.cfm@gwtrack.com',
            'John Reyes',
            $teamId,
            $rankSfa,
            $agencyOwner->id,
            [
                'country' => 'Canada',
                'province' => 'Alberta',
                'city' => 'Calgary',
                'timezone' => 'Canada Mountain Time',
                'phone' => '555-0102',
            ]
        );

        $james = $this->cfmUser(
            'james.cfm@gwtrack.com',
            'James Whitfield',
            $teamId,
            $rankSfa,
            $agencyOwner->id,
            [
                'country' => 'United States',
                'province' => 'California',
                'city' => 'Los Angeles',
                'timezone' => 'PST',
                'phone' => '555-0301',
            ]
        );

        $lisa = $this->cfmUser(
            'lisa.cfm@gwtrack.com',
            'Lisa Morgan',
            $teamId,
            $rankSfa,
            $agencyOwner->id,
            [
                'country' => 'United States',
                'province' => 'New York',
                'city' => 'New York',
                'timezone' => 'EST',
                'phone' => '555-0302',
            ]
        );

        $externalCfm = $this->externalCfm($agencyOwner, $rankSfa, $rankFa);

        $this->seedCfmJurisdictionProfiles();

        $this->seedCelesteScenario($celeste, $agencyOwner, $categoryId);
        $this->seedMariaScenario($maria, $agencyOwner, $rankFa, $categoryId);
        $this->seedJohnScenario($john, $agencyOwner, $rankFa, $categoryId);
        $this->seedJamesScenario($james, $agencyOwner, $rankFa, $categoryId);
        $this->seedLisaScenario($lisa, $agencyOwner, $rankFa, $categoryId);

        if ($externalCfm) {
            $this->seedExternalScenario($externalCfm, $agencyOwner, $categoryId);
        }

        $this->seedUnmentoredAssociates($agencyOwner, $teamId, $rankFa);
        $this->seedSuggestions($maria, $john, $externalCfm, $james, $lisa);

        app(DownlineHierarchyService::class)->rebuild();

        $this->logJurisdictionTestGuide();
    }

    private function seedCelesteScenario(User $celeste, User $agencyOwner, int $categoryId): void
    {
        $this->mentorTask($celeste, $agencyOwner, 'Confirm FAP step submissions for Maya Chen', 'open', now()->addDays(3));

        $maya = User::where('email', 'maya.fap@example.com')->first();
        if ($maya) {
            $assignment = $this->mentorAssignment($celeste, $maya, $agencyOwner, 'active', now()->subDays(45));
            $assignment->update([
                'confirmed_at' => now()->subDays(44),
                'first_contact_sent_at' => now()->subDays(43),
            ]);
        }

        $nina = User::where('email', 'nina.onboarding@example.com')->first();
        if ($nina) {
            $assignment = $this->mentorAssignment($celeste, $nina, $agencyOwner, 'active', now()->subDays(14));
            $assignment->update([
                'confirmed_at' => now()->subDays(13),
                'first_contact_sent_at' => now()->subDays(12),
            ]);
        }

        $completedApprentice = $this->member('celeste.completed@example.com', 'Jordan Ellis', $agencyOwner->team_id, $agencyOwner->id, null, Rank::where('code', 'TA')->value('id'));
        $this->seedAssociateLocation($completedApprentice, 'Canada', 'Ontario', 'Ottawa', 'Canada Eastern Time');
        $this->mentorAssignment($celeste, $completedApprentice, $agencyOwner, 'completed', now()->subMonths(8), now()->subMonths(2));

        $this->booking($celeste, $categoryId, now()->addDays(2)->setTime(10, 0), $maya);
        $this->booking($celeste, $categoryId, now()->addDays(4)->setTime(14, 0), $nina);
        $this->booking($celeste, $categoryId, now()->addDays(6)->setTime(11, 0), null);
    }

    private function seedMariaScenario(User $maria, User $agencyOwner, ?int $rankFa, int $categoryId): void
    {
        foreach (['maria.apprentice1@example.com', 'maria.apprentice2@example.com'] as $index => $email) {
            $apprentice = $this->member($email, 'Maria Apprentice '.($index + 1), $agencyOwner->team_id, $agencyOwner->id, $maria->id, $rankFa);
            $apprentice->profile()->updateOrCreate(
                ['user_id' => $apprentice->id],
                LocationOptions::profileAttributesForStorage([
                    'country' => 'Canada',
                    'province' => $index === 0 ? 'Ontario' : 'Quebec',
                    'city' => $index === 0 ? 'Toronto' : 'Montreal',
                    'timezone' => 'Canada Eastern Time',
                ])
            );
            $this->mentorAssignment($maria, $apprentice, $agencyOwner, 'active', now()->subDays(20 + $index));
            $this->seedApprenticeshipProgress($apprentice, $index === 0 ? 4 : 2);
        }

        foreach (range(1, 3) as $i) {
            $graduate = $this->member("maria.graduate{$i}@example.com", "Maria Graduate {$i}", $agencyOwner->team_id, $agencyOwner->id, null, $rankFa);
            $this->mentorAssignment($maria, $graduate, $agencyOwner, 'completed', now()->subMonths(10), now()->subMonths(3));
        }

        $sofiaId = User::where('email', 'sofia.needsmentor@example.com')->value('id');
        if ($sofiaId) {
            MentorAssignment::updateOrCreate(
                ['mentor_id' => $maria->id, 'apprentice_id' => $sofiaId, 'status' => 'pending'],
                ['assigned_by' => $agencyOwner->id, 'started_at' => null]
            );
        }

        $this->booking($maria, $categoryId, now()->addDays(1)->setTime(9, 0), User::where('email', 'maria.apprentice1@example.com')->first());
        $this->booking($maria, $categoryId, now()->addDays(3)->setTime(15, 30), null);
        $this->booking($maria, $categoryId, now()->addDays(5)->setTime(10, 0), null);
    }

    private function seedJohnScenario(User $john, User $agencyOwner, ?int $rankFa, int $categoryId): void
    {
        foreach (range(1, 7) as $i) {
            $apprentice = $this->member("john.apprentice{$i}@example.com", "John Apprentice {$i}", $agencyOwner->team_id, $agencyOwner->id, $john->id, $rankFa);
            $this->seedAssociateLocation($apprentice, 'Canada', 'Alberta', 'Calgary', 'Canada Mountain Time');
            $this->mentorAssignment($john, $apprentice, $agencyOwner, 'active', now()->subDays(40 + $i));
            if ($i <= 2) {
                $this->seedApprenticeshipProgress($apprentice, 1, stale: true);
            }
        }

        $this->mentorTask($john, $agencyOwner, 'Review overdue apprenticeship confirmations', 'overdue', now()->subDays(2));

        for ($day = 0; $day < 5; $day++) {
            $this->booking($john, $categoryId, now()->startOfWeek()->addDays($day)->setTime(9, 0), null);
            $this->booking($john, $categoryId, now()->startOfWeek()->addDays($day)->setTime(14, 0), null);
        }
        $this->booking($john, $categoryId, now()->startOfWeek()->addDay()->setTime(18, 0), null);
    }

    private function seedJamesScenario(User $james, User $agencyOwner, ?int $rankFa, int $categoryId): void
    {
        $apprentices = [
            ['james.apprentice.ca@example.com', 'James Apprentice — California', 'California', 'Los Angeles', 'PST'],
            ['james.apprentice.tx@example.com', 'James Apprentice — Texas', 'Texas', 'Houston', 'CST'],
        ];

        foreach ($apprentices as $index => [$email, $name, $state, $city, $timezone]) {
            $apprentice = $this->member($email, $name, $agencyOwner->team_id, $agencyOwner->id, $james->id, $rankFa);
            $this->seedAssociateLocation($apprentice, 'United States', $state, $city, $timezone);
            $this->mentorAssignment($james, $apprentice, $agencyOwner, 'active', now()->subDays(15 + $index));
            $this->seedApprenticeshipProgress($apprentice, 2);
        }

        $this->booking($james, $categoryId, now()->addDays(2)->setTime(11, 0), User::where('email', 'james.apprentice.ca@example.com')->first());
        $this->booking($james, $categoryId, now()->addDays(4)->setTime(13, 0), null);
    }

    private function seedLisaScenario(User $lisa, User $agencyOwner, ?int $rankFa, int $categoryId): void
    {
        $apprentices = [
            ['lisa.apprentice.ny@example.com', 'Lisa Apprentice — New York', 'New York', 'New York', 'EST'],
            ['lisa.apprentice.fl@example.com', 'Lisa Apprentice — Florida', 'Florida', 'Miami', 'EST'],
        ];

        foreach ($apprentices as $index => [$email, $name, $state, $city, $timezone]) {
            $apprentice = $this->member($email, $name, $agencyOwner->team_id, $agencyOwner->id, $lisa->id, $rankFa);
            $this->seedAssociateLocation($apprentice, 'United States', $state, $city, $timezone);
            $this->mentorAssignment($lisa, $apprentice, $agencyOwner, 'active', now()->subDays(12 + $index));
            $this->seedApprenticeshipProgress($apprentice, 3);
        }

        $graduate = $this->member('lisa.graduate.ca@example.com', 'Lisa Graduate — California', $agencyOwner->team_id, $agencyOwner->id, null, $rankFa);
        $this->seedAssociateLocation($graduate, 'United States', 'California', 'San Diego', 'PST');
        $this->mentorAssignment($lisa, $graduate, $agencyOwner, 'completed', now()->subMonths(6), now()->subMonths(2));

        $this->booking($lisa, $categoryId, now()->addDays(1)->setTime(10, 30), User::where('email', 'lisa.apprentice.ny@example.com')->first());
    }

    private function seedExternalScenario(User $external, User $agencyOwner, int $categoryId): void
    {
        DB::table('team_visibility_permissions')->updateOrInsert(
            ['viewer_id' => $agencyOwner->id, 'visible_user_id' => $external->id],
            [
                'granted_by' => $agencyOwner->id,
                'can_view_sensitive_data' => false,
                'can_export' => false,
                'expires_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->mentorTask($external, $agencyOwner, 'Review overdue apprenticeship confirmations', 'overdue', now()->subDays(2));

        for ($day = 0; $day < 6; $day++) {
            $this->booking($external, $categoryId, now()->startOfWeek()->addDays($day)->setTime(10, 0), null);
            $this->booking($external, $categoryId, now()->startOfWeek()->addDays($day)->setTime(16, 0), null);
        }
    }

    private function seedUnmentoredAssociates(User $agencyOwner, ?int $teamId, ?int $rankFa): void
    {
        $queue = $this->fapQueueJurisdictionScenarios();

        foreach ($queue as $index => $row) {
            $associate = $this->member($row['email'], $row['name'], $teamId, $agencyOwner->id, null, $rankFa);
            $associate->profile()->updateOrCreate(
                ['user_id' => $associate->id],
                LocationOptions::profileAttributesForStorage([
                    'country' => $row['country'],
                    'province' => $row['province'],
                    'city' => $row['city'],
                    'timezone' => $row['timezone'],
                    'bio' => $row['queue_label'],
                    'phone' => '555-02'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'gw_associate_id' => 'GW-QUEUE-'.$associate->id,
                    'is_gw_active_associate' => true,
                    'recruited_at' => now()->subDays(3)->toDateString(),
                ])
            );
        }
    }

    private function seedSuggestions(?User $maria, ?User $john, ?User $external, ?User $james = null, ?User $lisa = null): void
    {
        $rows = [
            [
                'recommendation_type' => 'best_fit',
                'label' => 'Recommended',
                'cfm_user_id' => $maria?->id,
                'fit_score' => 92,
                'status_label' => 'Recommended',
                'detail' => 'Best CFM for this associate based on load, timezone, licensed jurisdiction, and FAP completion rate.',
                'sort_order' => 1,
            ],
            [
                'recommendation_type' => 'jurisdiction',
                'label' => 'Licensed in associate province',
                'cfm_user_id' => $maria?->id,
                'fit_score' => 95,
                'status_label' => 'Jurisdiction match',
                'detail' => 'Maria Santos is licensed in Ontario and Quebec. Assign modal: pick the associate first — only licensed CFMs appear (see FAP queue names for expected matches).',
                'sort_order' => 6,
            ],
            [
                'recommendation_type' => 'jurisdiction',
                'label' => 'US license match',
                'cfm_user_id' => $james?->id,
                'fit_score' => 90,
                'status_label' => 'US jurisdiction',
                'detail' => 'James Whitfield is licensed in California and Texas — use US FAP queue associates in those states.',
                'sort_order' => 7,
            ],
            [
                'recommendation_type' => 'jurisdiction',
                'label' => 'US East license match',
                'cfm_user_id' => $lisa?->id,
                'fit_score' => 88,
                'status_label' => 'US jurisdiction',
                'detail' => 'Lisa Morgan is licensed in New York and Florida — not licensed in California or Texas.',
                'sort_order' => 8,
            ],
            [
                'recommendation_type' => 'caution',
                'label' => 'Use Caution',
                'cfm_user_id' => $john?->id,
                'fit_score' => 61,
                'status_label' => 'Use Caution',
                'detail' => 'High workload — confirm calendar capacity before assigning.',
                'sort_order' => 2,
            ],
            [
                'recommendation_type' => 'not_recommended',
                'label' => 'Not Recommended',
                'cfm_user_id' => $external?->id,
                'fit_score' => 38,
                'status_label' => 'Not Recommended',
                'detail' => 'Overloaded this week with limited open mentor slots.',
                'sort_order' => 3,
            ],
            [
                'recommendation_type' => 'timezone',
                'label' => 'Timezone match available',
                'cfm_user_id' => null,
                'fit_score' => null,
                'status_label' => null,
                'detail' => 'CFM has similar timezone to the selected associate.',
                'sort_order' => 4,
            ],
            [
                'recommendation_type' => 'calendar',
                'label' => 'Open calendar slots this week',
                'cfm_user_id' => $maria?->id,
                'fit_score' => null,
                'status_label' => null,
                'detail' => 'CFM has available mentor sessions in the next 7 days.',
                'sort_order' => 5,
            ],
        ];

        foreach ($rows as $row) {
            $cfmName = $row['cfm_user_id']
                ? User::whereKey($row['cfm_user_id'])->value('name')
                : null;

            CfmRecommendationSuggestion::updateOrCreate(
                ['recommendation_type' => $row['recommendation_type'], 'sort_order' => $row['sort_order']],
                array_merge($row, ['cfm_name' => $cfmName, 'is_active' => true])
            );
        }
    }

    private function externalCfm(User $agencyOwner, ?int $rankSfa, ?int $rankFa): ?User
    {
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'Pacific Northwest Agency',
            'description' => 'External demo agency for cross-hierarchy CFM visibility.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::updateOrCreate(
            ['email' => 'external.cfm@gwtrack.com'],
            [
                'name' => 'David Kim',
                'password' => Hash::make('Password123'),
                'rank_id' => $rankSfa,
                'team_id' => $teamId,
                'sponsor_id' => null,
                'mentor_id' => null,
                'is_active' => true,
                'joined_at' => now()->subYear(),
            ]
        );

        $user->syncRoles(['certified-field-mentor']);
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            LocationOptions::profileAttributesForStorage([
                'country' => 'Canada',
                'province' => 'British Columbia',
                'city' => 'Victoria',
                'timezone' => 'Canada Pacific Time',
                'phone' => '555-0199',
            ])
        );

        for ($i = 1; $i <= 9; $i++) {
            $apprentice = $this->member(
                "david.apprentice{$i}@example.com",
                "David Apprentice {$i}",
                $teamId,
                null,
                $user->id,
                $rankFa
            );
            $this->mentorAssignment($user, $apprentice, $agencyOwner, 'active', now()->subDays(30 + $i));
        }

        return $user;
    }

    private function cfmUser(string $email, string $name, ?int $teamId, ?int $rankId, int $sponsorId, array $profile): User
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
            ]
        );

        $user->syncRoles(['certified-field-mentor']);
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            LocationOptions::profileAttributesForStorage($profile)
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function normalizeProfileLocation(array $profile): array
    {
        $country = $profile['country'] ?? 'Canada';
        $province = $profile['province'] ?? null;
        $timezone = $profile['timezone'] ?? null;

        unset($profile['country'], $profile['province'], $profile['timezone']);

        return array_merge($profile, LocationOptions::profileLocationIds($country, $province, $timezone));
    }

    private function member(string $email, string $name, ?int $teamId, ?int $sponsorId, ?int $mentorId, ?int $rankId): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $teamId,
                'sponsor_id' => $sponsorId,
                'mentor_id' => $mentorId,
                'is_active' => true,
            ]
        );

        $user->syncRoles(['member']);

        return $user;
    }

    private function mentorAssignment(
        User $mentor,
        User $apprentice,
        User $assignedBy,
        string $status,
        Carbon $startedAt,
        ?Carbon $completedAt = null
    ): MentorAssignment {
        if ($status === 'active') {
            $apprentice->update(['mentor_id' => $mentor->id]);
        }

        return MentorAssignment::updateOrCreate(
            ['mentor_id' => $mentor->id, 'apprentice_id' => $apprentice->id, 'status' => $status],
            [
                'assigned_by' => $assignedBy->id,
                'started_at' => $startedAt->toDateString(),
                'completed_at' => $completedAt?->toDateString(),
            ]
        );
    }

    private function mentorTask(User $cfm, User $creator, string $title, string $status, Carbon $dueDate): void
    {
        $libraryTask = TaskLibrary::findOrCreate(
            $title,
            'CFM Mentorship',
            'Seeded CFM mentorship task for management dashboard testing.',
            $status === 'overdue' ? 'high' : 'medium',
        );

        TaskUser::updateOrCreate(
            ['assignee_id' => $cfm->id, 'task_id' => $libraryTask->id],
            [
                'task_category_id' => $libraryTask->task_category_id,
                'assignor_id' => $creator->id,
                'priority' => $status === 'overdue' ? 'high' : 'medium',
                'status' => $status,
                'related_module' => 'Training',
                'due_date' => $dueDate->toDateString(),
                'progress' => $status === 'overdue' ? 40 : 0,
            ]
        );
    }

    private function seedApprenticeshipProgress(User $apprentice, int $completedSteps, bool $stale = false): void
    {
        $stepIds = Checklist::query()
            ->forTypeCode('fap')
            ->active()
            ->orderBy('sort_order')
            ->limit(6)
            ->pluck('id');

        foreach ($stepIds as $index => $stepId) {
            $isCompleted = $index < $completedSteps;

            ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $apprentice->id,
                    'checklist_id' => $stepId,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => $isCompleted ? 'completed' : ($stale ? 'not_started' : 'in_progress'),
                    'completed_at' => $isCompleted ? now()->subDays(10 - $index) : null,
                    'updated_at' => $stale && ! $isCompleted ? now()->subDays(21) : now()->subDays(2),
                ],
            );
        }
    }

    private function booking(User $cfm, int $categoryId, Carbon $startsAt, ?User $trainee): void
    {
        $type = BookingEventType::query()->firstOrCreate(
            ['owner_id' => $cfm->id, 'slug' => 'cfm-management-session'],
            [
                'calendar_category_id' => $categoryId,
                'title' => 'CFM Mentor Session',
                'description' => 'Seeded mentor session for CFM management dashboard.',
                'duration_minutes' => 60,
                'event_category' => 'mentor_session',
                'location_type' => 'zoom',
                'meeting_link' => 'https://zoom.us/j/demo',
                'is_active' => true,
            ]
        );

        Booking::query()->updateOrCreate(
            [
                'booking_event_type_id' => $type->id,
                'cfm_id' => $cfm->id,
                'starts_at' => $startsAt,
            ],
            [
                'public_id' => (string) Str::ulid(),
                'trainee_id' => $trainee?->id,
                'status' => 'confirmed',
                'ends_at' => $startsAt->copy()->addHour(),
                'timezone' => $cfm->profile?->timezone ?? 'America/Toronto',
                'location_type' => 'zoom',
                'confirmed_at' => now(),
            ]
        );
    }

    private function calendarCategoryId(): int
    {
        return CalendarCategory::query()->firstOrCreate(
            ['slug' => 'cfm-mentor-sessions'],
            ['name' => 'CFM Mentor Sessions', 'color' => '#84BB41', 'icon' => 'award', 'sort_order' => 75, 'is_active' => true]
        )->id;
    }

    private function seedProfile(User $user, array $attributes): void
    {
        CfmMentorProfile::updateOrCreate(['user_id' => $user->id], $attributes);
    }

    /**
     * Demo CFM licensed jurisdictions + home locations for assignment filtering tests.
     *
     * @return array<string, array{location: array<string, string>, mentor: array<string, mixed>}>
     */
    private function cfmJurisdictionDefinitions(): array
    {
        return [
            'cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'Canada',
                    'province' => 'Ontario',
                    'city' => 'Toronto',
                    'timezone' => 'Canada Eastern Time',
                    'phone' => '555-0140',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'fap_completion_rate' => 0,
                    'calendar_busyness_percent' => 0,
                    'avg_apprentice_progress' => 0,
                    'recommendation_score' => 88,
                    'languages' => ['English', 'Spanish'],
                    'specialties' => ['Field Apprenticeship', 'Licensing Support'],
                    'licensed_jurisdictions' => ['Canada|Ontario', 'Canada|British Columbia'],
                    'mentor_bio' => 'Licensed in Ontario and BC. Use with FAP queue Ontario/BC or apprentice Maya Chen (BC).',
                    'last_mentor_activity_at' => now()->subHours(6),
                ],
            ],
            'maria.cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'Canada',
                    'province' => 'Ontario',
                    'city' => 'Toronto',
                    'timezone' => 'Canada Eastern Time',
                    'phone' => '555-0101',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'recommendation_score' => 92,
                    'languages' => ['English', 'Spanish'],
                    'specialties' => ['Field Apprenticeship', 'New Associate Onboarding'],
                    'licensed_jurisdictions' => ['Canada|Ontario', 'Canada|Quebec'],
                    'mentor_bio' => 'Licensed in Ontario and Quebec. Best match for FAP queue Ontario or Quebec associates.',
                    'last_mentor_activity_at' => now()->subHours(2),
                ],
            ],
            'john.cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'Canada',
                    'province' => 'Alberta',
                    'city' => 'Calgary',
                    'timezone' => 'Canada Mountain Time',
                    'phone' => '555-0102',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'recommendation_score' => 61,
                    'languages' => ['English'],
                    'specialties' => ['Prospect Support', 'Rank Advancement'],
                    'licensed_jurisdictions' => ['Canada|Alberta', 'Canada|British Columbia', 'Canada|Saskatchewan'],
                    'mentor_bio' => 'Licensed in Alberta, BC, and Saskatchewan. Use with FAP queue Alberta or BC.',
                    'last_mentor_activity_at' => now()->subDay(),
                ],
            ],
            'external.cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'Canada',
                    'province' => 'British Columbia',
                    'city' => 'Victoria',
                    'timezone' => 'Canada Pacific Time',
                    'phone' => '555-0199',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'admin_approved',
                    'max_apprentices' => 6,
                    'recommendation_score' => 38,
                    'languages' => ['English'],
                    'specialties' => ['Rank Advancement'],
                    'licensed_jurisdictions' => ['Canada|British Columbia'],
                    'mentor_bio' => 'External CFM — BC license only. Visible to agency owner for cross-hierarchy assign tests.',
                    'last_mentor_activity_at' => now()->subDays(2),
                ],
            ],
            'james.cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'United States',
                    'province' => 'California',
                    'city' => 'Los Angeles',
                    'timezone' => 'PST',
                    'phone' => '555-0301',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'recommendation_score' => 85,
                    'languages' => ['English'],
                    'specialties' => ['Field Apprenticeship', 'US Licensing'],
                    'licensed_jurisdictions' => ['United States|California', 'United States|Texas'],
                    'mentor_bio' => 'US CFM licensed in California and Texas. Assign US queue CA/TX associates only.',
                    'last_mentor_activity_at' => now()->subHours(4),
                ],
            ],
            'lisa.cfm@gwtrack.com' => [
                'location' => [
                    'country' => 'United States',
                    'province' => 'New York',
                    'city' => 'New York',
                    'timezone' => 'EST',
                    'phone' => '555-0302',
                ],
                'mentor' => [
                    'certification_status' => 'certified',
                    'hierarchy_access' => 'my_hierarchy',
                    'max_apprentices' => 6,
                    'recommendation_score' => 90,
                    'languages' => ['English'],
                    'specialties' => ['Field Apprenticeship', 'East Coast Markets'],
                    'licensed_jurisdictions' => ['United States|New York', 'United States|Florida'],
                    'mentor_bio' => 'US CFM licensed in New York and Florida. Use for US queue NY/FL associates.',
                    'last_mentor_activity_at' => now()->subHours(3),
                ],
            ],
        ];
    }

    private function seedCfmJurisdictionProfiles(): void
    {
        foreach ($this->cfmJurisdictionDefinitions() as $email => $definition) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                continue;
            }

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                LocationOptions::profileAttributesForStorage($definition['location'])
            );

            $this->seedProfile($user, $definition['mentor']);
        }
    }

    /**
     * Unmentored associates for CFM Management → Assign / FAP queue jurisdiction tests.
     *
     * @return list<array{email: string, name: string, queue_label: string, city: string, province: string, timezone: string, country: string, licensed_cfms: string}>
     */
    private function fapQueueJurisdictionScenarios(): array
    {
        return [
            [
                'email' => 'fap.queue1@example.com',
                'name' => 'Owen Taylor',
                'queue_label' => 'FAP Queue — Ontario → Celeste, Maria',
                'city' => 'Toronto',
                'province' => 'Ontario',
                'country' => 'Canada',
                'timezone' => 'Canada Eastern Time',
                'licensed_cfms' => 'Celeste Navarro, Maria Santos',
            ],
            [
                'email' => 'fap.queue2@example.com',
                'name' => 'Quinn Martin',
                'queue_label' => 'FAP Queue — Quebec → Maria only',
                'city' => 'Montreal',
                'province' => 'Quebec',
                'country' => 'Canada',
                'timezone' => 'Canada Eastern Time',
                'licensed_cfms' => 'Maria Santos',
            ],
            [
                'email' => 'fap.queue3@example.com',
                'name' => 'Albert Reyes',
                'queue_label' => 'FAP Queue — Alberta → John only',
                'city' => 'Calgary',
                'province' => 'Alberta',
                'country' => 'Canada',
                'timezone' => 'Canada Mountain Time',
                'licensed_cfms' => 'John Reyes',
            ],
            [
                'email' => 'fap.queue4@example.com',
                'name' => 'Morgan Lee',
                'queue_label' => 'FAP Queue — Manitoba → none (blocked)',
                'city' => 'Winnipeg',
                'province' => 'Manitoba',
                'country' => 'Canada',
                'timezone' => 'Canada Central Time',
                'licensed_cfms' => 'None — empty CFM list in Assign',
            ],
            [
                'email' => 'fap.queue5@example.com',
                'name' => 'Blair Chen',
                'queue_label' => 'FAP Queue — BC → Celeste, John, David',
                'city' => 'Vancouver',
                'province' => 'British Columbia',
                'country' => 'Canada',
                'timezone' => 'Canada Pacific Time',
                'licensed_cfms' => 'Celeste Navarro, John Reyes, David Kim (external)',
            ],
            [
                'email' => 'fap.queue.us-ca@example.com',
                'name' => 'Caleb Morris',
                'queue_label' => 'FAP Queue — California → James',
                'city' => 'Los Angeles',
                'province' => 'California',
                'country' => 'United States',
                'timezone' => 'PST',
                'licensed_cfms' => 'James Whitfield',
            ],
            [
                'email' => 'fap.queue.us-tx@example.com',
                'name' => 'Dallas Brooks',
                'queue_label' => 'FAP Queue — Texas → James',
                'city' => 'Houston',
                'province' => 'Texas',
                'country' => 'United States',
                'timezone' => 'CST',
                'licensed_cfms' => 'James Whitfield',
            ],
            [
                'email' => 'fap.queue.us-fl@example.com',
                'name' => 'Fiona Grant',
                'queue_label' => 'FAP Queue — Florida → Lisa only',
                'city' => 'Miami',
                'province' => 'Florida',
                'country' => 'United States',
                'timezone' => 'EST',
                'licensed_cfms' => 'Lisa Morgan',
            ],
            [
                'email' => 'fap.queue.us-ny@example.com',
                'name' => 'Nina York',
                'queue_label' => 'FAP Queue — New York → Lisa',
                'city' => 'New York',
                'province' => 'New York',
                'country' => 'United States',
                'timezone' => 'EST',
                'licensed_cfms' => 'Lisa Morgan',
            ],
            [
                'email' => 'fap.queue.us-wa@example.com',
                'name' => 'Walter Stone',
                'queue_label' => 'FAP Queue — Washington → none (blocked)',
                'city' => 'Seattle',
                'province' => 'Washington',
                'country' => 'United States',
                'timezone' => 'PST',
                'licensed_cfms' => 'None — no US CFM licensed in WA',
            ],
        ];
    }

    private function seedAssociateLocation(
        User $user,
        string $country,
        string $province,
        string $city,
        string $timezone
    ): void {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            LocationOptions::profileAttributesForStorage([
                'country' => $country,
                'province' => $province,
                'city' => $city,
                'timezone' => $timezone,
            ])
        );
    }

    private function logJurisdictionTestGuide(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('CFM jurisdiction demo data seeded.');
        $this->command->line('  Login: agency-owner@gwtrack.com / Password123 → CFM Management → Assign');
        $this->command->line('  Licensed jurisdictions:');
        foreach ($this->cfmJurisdictionDefinitions() as $email => $definition) {
            $licensed = implode(', ', $definition['mentor']['licensed_jurisdictions'] ?? []);
            $this->command->line("    {$email}: {$licensed}");
        }
        $this->command->line('  FAP queue (select associate first in Assign):');
        foreach ($this->fapQueueJurisdictionScenarios() as $row) {
            $this->command->line("    {$row['email']} — {$row['licensed_cfms']}");
        }
        $this->command->line('  Also try: maya.fap@example.com (BC) with Celeste; sofia.needsmentor@example.com (QC) with Maria.');
        $this->command->line('  US CFMs: james.cfm@gwtrack.com (CA, TX) · lisa.cfm@gwtrack.com (NY, FL) — Password123');
        $this->command->line('  US apprentices: james.apprentice.* / lisa.apprentice.* · US queue: fap.queue.us-*@example.com');
    }

    private function seedRankStructure(): void
    {
        $tiers = [
            ['sort_order' => 1, 'title' => 'Associate Mentor', 'criteria' => 'Completed basic training · 0-1 apprentices · 6 months experience', 'next_step' => '10 apprentices mentored'],
            ['sort_order' => 2, 'title' => 'CFM I', 'criteria' => '3+ apprentices graduated · 1 year experience · 80%+ completion rate', 'next_step' => '25 apprentices · 90% rate'],
            ['sort_order' => 3, 'title' => 'CFM II', 'criteria' => '10+ graduates · 2+ years · 85%+ rate · Team leadership', 'next_step' => '50 apprentices · Mentor new CFMs'],
            ['sort_order' => 4, 'title' => 'Senior CFM', 'criteria' => '25+ graduates · 4+ years · 90%+ rate · Regional influence', 'next_step' => '100 apprentices · National recognition'],
            ['sort_order' => 5, 'title' => 'Master CFM', 'criteria' => '50+ graduates · 7+ years · 92%+ rate · trains other CFMs', 'next_step' => 'Executive nomination'],
            ['sort_order' => 6, 'title' => 'Executive CFM', 'criteria' => '100+ graduates · 10+ years · 95%+ rate · Program design', 'next_step' => 'Top 1% of mentors'],
            ['sort_order' => 7, 'title' => 'Director of Field Mentorship', 'criteria' => 'Strategic leadership · National impact · Board advisor', 'next_step' => 'Highest recognition'],
            ['sort_order' => 8, 'title' => 'Hall of Fame', 'icon' => 'trophy', 'criteria' => 'Lifetime achievement · 200+ graduates · 15+ years', 'next_step' => null],
        ];

        foreach ($tiers as $tier) {
            CfmRankTier::updateOrCreate(
                ['sort_order' => $tier['sort_order']],
                array_merge($tier, ['is_active' => true])
            );
        }

        CfmAdvancementGuideline::updateOrCreate(
            ['id' => 1],
            [
                'body' => 'How to advance: Complete FAP mentorships, maintain high completion rate (>85%), zero overdue tasks, positive apprentice feedback, annual recertification, and contribute to CFM training programs.',
                'is_active' => true,
            ]
        );
    }
}
