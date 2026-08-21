<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\Rank;
use App\Models\User;
use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TaskScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $teamId = $this->teamId();
        $ranks = Rank::query()->pluck('id', 'code');

        $agencyOwner = $this->user(
            'agency-owner@gwtrack.com',
            'Arielle Morgan',
            'agency-owner',
            $ranks['SVP'] ?? $ranks['MB'] ?? null,
            $teamId
        );

        DB::table('teams')->where('id', $teamId)->update([
            'owner_id' => $agencyOwner->id,
            'leader_id' => $agencyOwner->id,
            'updated_at' => now(),
        ]);

        $sponsor = $this->user(
            'sponsor@gwtrack.com',
            'Marcus Rivera',
            'team-leader',
            $ranks['SA'] ?? null,
            $teamId,
            ['sponsor_id' => $agencyOwner->id]
        );

        $cfm = $this->user(
            'cfm@gwtrack.com',
            'Celeste Navarro',
            'certified-field-mentor',
            $ranks['ASC'] ?? null,
            $teamId,
            ['sponsor_id' => $agencyOwner->id]
        );

        $trainer = $this->user(
            'trainer@gwtrack.com',
            'Tristan Blake',
            'trainer',
            $ranks['ASC'] ?? null,
            $teamId,
            ['sponsor_id' => $agencyOwner->id]
        );

        $onboardingMember = $this->member('nina.onboarding@example.com', 'Nina Santos', $teamId, $agencyOwner->id, $cfm->id);
        $licensingMember = $this->member('leo.licensing@example.com', 'Leo Grant', $teamId, $agencyOwner->id, $cfm->id);
        $fapMember = $this->member('maya.fap@example.com', 'Maya Chen', $teamId, $agencyOwner->id, $cfm->id);
        $cfmCandidate = $this->member('owen.cfm@example.com', 'Owen Patel', $teamId, $agencyOwner->id, null, $ranks['ASC'] ?? null);
        $needsMentor = $this->member('sofia.needsmentor@example.com', 'Sofia Reyes', $teamId, $agencyOwner->id, null);
        $usProspect = $this->member('aaron.us@example.com', 'Aaron Brooks', $teamId, $agencyOwner->id, null);
        $jordan = $this->member('jordan.ellis@example.com', 'Jordan Ellis', $teamId, $sponsor->id, $cfm->id);
        $dana = $this->member('dana.foster@example.com', 'Dana Foster', $teamId, $sponsor->id, $cfm->id);
        $priya = $this->member('priya.sharma@example.com', 'Priya Sharma', $teamId, $sponsor->id, $cfm->id);
        $taylor = $this->user(
            'taylor.kim@example.com',
            'Taylor Kim',
            'new-recruit',
            $ranks['TA'] ?? null,
            $teamId,
            ['sponsor_id' => $agencyOwner->id, 'joined_at' => now()->subDays(2)]
        );

        $this->profile($cfm, [
            'city' => 'Toronto',
            'province' => 'Ontario',
            'timezone' => 'Canada Eastern Time',
            'phone' => '555-0140',
        ]);
        $this->profile($fapMember, [
            'city' => 'Vancouver',
            'province' => 'British Columbia',
            'timezone' => 'Canada Pacific Time',
        ]);
        $this->profile($onboardingMember, [
            'city' => 'Toronto',
            'province' => 'Ontario',
            'timezone' => 'Canada Eastern Time',
        ]);
        $this->profile($licensingMember, [
            'city' => 'Calgary',
            'province' => 'Alberta',
            'timezone' => 'Canada Mountain Time',
        ]);
        $this->profile($needsMentor, [
            'city' => 'Montreal',
            'province' => 'Quebec',
            'timezone' => 'Canada Eastern Time',
        ]);
        $this->profile($usProspect, [
            'city' => 'Chicago',
            'province' => 'Illinois',
            'country' => 'United States',
            'timezone' => 'CST',
        ]);
        $this->profile($jordan, [
            'city' => 'Edmonton',
            'province' => 'Alberta',
            'timezone' => 'Canada Mountain Time',
        ]);
        $this->profile($dana, [
            'city' => 'Ottawa',
            'province' => 'Ontario',
            'timezone' => 'Canada Eastern Time',
        ]);
        $this->profile($priya, [
            'city' => 'Winnipeg',
            'province' => 'Manitoba',
            'timezone' => 'Canada Central Time',
        ]);
        $this->profile($taylor, [
            'city' => 'Halifax',
            'province' => 'Nova Scotia',
            'timezone' => 'Canada Atlantic Time',
        ]);

        $this->pendingChecklist('onboarding', 'Complete Member Profile', $onboardingMember->id, now()->subDays(3));
        $this->pendingChecklist('licensing', 'Pass Licensing Exam', $licensingMember->id, now()->subDays(2));
        $this->pendingChecklist('fap', 'Receive FAP Approval', $fapMember->id, now()->subDay());
        $this->pendingChecklist('cfm-training', 'CFM Certification Review', $cfmCandidate->id, now()->subDays(4));

        $this->invitation($agencyOwner->id, 'prospect.one@example.com');
        $this->invitation($agencyOwner->id, 'prospect.two@example.com');
        $this->rankReview($licensingMember->id, $ranks['ASC'] ?? null);

        $needsMentor->update([
            'joined_at' => now()->subDays(5),
            'mentor_id' => null,
        ]);

        $trainer->update(['mentor_id' => $cfm->id]);
        $sponsor->update(['mentor_id' => $cfm->id]);
        $taylor->update(['joined_at' => now()->subDays(2), 'mentor_id' => null]);
    }

    private function teamId(): int
    {
        DB::table('teams')->updateOrInsert(
            ['name' => 'Wealth Legacy Alliance'],
            [
                'description' => 'Demo team seeded for task-center scenarios.',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return (int) DB::table('teams')->where('name', 'Wealth Legacy Alliance')->value('id');
    }

    private function user(string $email, string $name, string $role, ?int $rankId, int $teamId, array $attributes = []): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $teamId,
                'is_active' => true,
                'joined_at' => $attributes['joined_at'] ?? now()->subDays(10),
                'is_online' => false,
                ...$attributes,
            ]
        );

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);

        $this->profile($user);

        return $user;
    }

    private function member(string $email, string $name, int $teamId, int $sponsorId, ?int $mentorId, ?int $rankId = null): User
    {
        $rankId ??= Rank::where('code', 'TA')->value('id');

        return $this->user($email, $name, 'member', $rankId, $teamId, [
            'sponsor_id' => $sponsorId,
            'mentor_id' => $mentorId,
            'joined_at' => now()->subDays(6),
        ]);
    }

    private function profile(User $user, array $overrides = []): void
    {
        $defaults = [
            'phone' => '555-0100',
            'city' => 'Vancouver',
            'country' => 'Canada',
            'province' => 'British Columbia',
            'timezone' => 'Canada Pacific Time',
            'gw_associate_id' => 'GW-DEMO-'.$user->id,
            'is_gw_active_associate' => true,
            'recruited_at' => now()->subDays(6)->toDateString(),
        ];

        $data = array_merge($defaults, $overrides);
        $locationIds = LocationOptions::profileLocationIds(
            $data['country'] ?? 'Canada',
            $data['province'] ?? null,
            $data['timezone'] ?? null,
        );

        unset($data['country'], $data['province'], $data['timezone']);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, $locationIds)
        );
    }

    private function pendingChecklist(string $typeCode, string $title, int $userId, $submittedAt): void
    {
        $checklistId = Checklist::query()
            ->forTypeCode($typeCode)
            ->where('title', $title)
            ->value('id');

        if (! $checklistId) {
            return;
        }

        ChecklistProgress::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'checklist_id' => $checklistId,
                'mentor_assignment_id' => null,
            ],
            [
                'status' => 'pending_confirmation',
                'submitted_at' => $submittedAt,
                'completed_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_comments' => null,
            ],
        );
    }

    private function invitation(int $sponsorId, string $email): void
    {
        DB::table('registration_invitations')->updateOrInsert(
            [
                'sponsor_id' => $sponsorId,
                'email' => $email,
            ],
            [
                'accepted_by' => null,
                'code' => Str::upper(Str::random(12)),
                'role_name' => 'member',
                'max_uses' => 1,
                'uses_count' => 0,
                'expires_at' => now()->addDays(14),
                'accepted_at' => null,
                'last_emailed_at' => null,
                'revoked_at' => null,
                'deleted_at' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]
        );
    }

    private function rankReview(int $userId, ?int $rankId): void
    {
        if (! $rankId) {
            return;
        }

        DB::table('rank_requirements')->updateOrInsert(
            [
                'rank_id' => $rankId,
                'title' => 'Complete Licensing And FAP Readiness Review',
            ],
            [
                'description' => 'Demo rank advancement item waiting for leadership review.',
                'sort_order' => 10,
                'deleted_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]
        );

        $requirementId = DB::table('rank_requirements')
            ->where('rank_id', $rankId)
            ->where('title', 'Complete Licensing And FAP Readiness Review')
            ->value('id');

        DB::table('user_rank_progress')->updateOrInsert(
            [
                'user_id' => $userId,
                'rank_requirement_id' => $requirementId,
            ],
            [
                'status' => 'ready_for_review',
                'completed_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]
        );
    }
}
