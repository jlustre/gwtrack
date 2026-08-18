<?php

namespace Database\Seeders;

use App\Models\Prospect;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProspectDemoSeeder extends Seeder
{
    public function run(): void
    {
        $teamId = $this->teamId();
        $rankId = Rank::where('code', 'FA')->value('id');

        $owner = $this->user('prospects@gwtrack.com', 'Prospect Demo Owner', 'member', $rankId, $teamId);
        $cfm = $this->user('prospect-cfm@gwtrack.com', 'Prospect CFM Coach', 'certified-field-mentor', Rank::where('code', 'SFA')->value('id'), $teamId);
        $trainer = $this->user('prospect-trainer@gwtrack.com', 'Prospect Trainer', 'trainer', Rank::where('code', 'SFA')->value('id'), $teamId);

        $sourceIds = DB::table('prospect_sources')->pluck('id')->all();
        $stageIds = DB::table('pipeline_stages')->whereNull('user_id')->pluck('id')->all();
        $typeIds = DB::table('prospect_types')->pluck('id')->all();
        $interestIds = DB::table('prospect_interests')->pluck('id')->all();
        $tagIds = DB::table('prospect_tags')->whereNull('user_id')->pluck('id')->all();
        $communicationTypeIds = DB::table('communication_types')->pluck('id')->all();
        $appointmentTypeIds = DB::table('appointment_types')->pluck('id')->all();
        $sharePermissionId = DB::table('prospect_share_permissions')->where('key', 'full_collaboration')->value('id');

        $firstNames = [
            'Avery', 'Jordan', 'Morgan', 'Taylor', 'Riley', 'Casey', 'Quinn', 'Cameron', 'Drew', 'Reese',
            'Parker', 'Skyler', 'Rowan', 'Alex', 'Jamie', 'Kendall', 'Harper', 'Emerson', 'Finley', 'Hayden',
            'Marlon', 'Bianca', 'Andre', 'Selena', 'Victor', 'Nadia', 'Elena', 'Marco', 'Priya', 'Dante',
            'Camila', 'Jonas', 'Mei', 'Luis', 'Amara', 'Noah', 'Isla', 'Mateo', 'Sienna', 'Theo',
            'Lena', 'Kai', 'Mila', 'Elias', 'Nora', 'Ezra', 'Aria', 'Luca', 'Zara', 'Owen',
        ];

        $lastNames = [
            'Carter', 'Bennett', 'Morgan', 'Reyes', 'Patel', 'Chen', 'Rivera', 'Nguyen', 'Santos', 'Brooks',
            'Foster', 'Mitchell', 'Hughes', 'Diaz', 'Ramos', 'Bell', 'Cooper', 'Flores', 'James', 'Watson',
        ];

        foreach ($firstNames as $index => $firstName) {
            $lastName = $lastNames[$index % count($lastNames)];
            $stageId = $stageIds[$index % count($stageIds)] ?? null;
            $sourceId = $sourceIds[$index % count($sourceIds)] ?? null;
            $interestLevel = ['cold', 'warm', 'hot'][$index % 3];
            $priority = ['low', 'medium', 'high', 'urgent'][$index % 4];
            $nextFollowUp = now()->addDays(($index % 9) - 3)->setTime(10 + ($index % 6), 0);
            $lastContacted = now()->subDays($index % 14)->setTime(9 + ($index % 7), 30);

            $prospect = Prospect::updateOrCreate(
                [
                    'owner_id' => $owner->id,
                    'email' => Str::slug($firstName.'.'.$lastName).'.demo@example.com',
                ],
                [
                    'prospect_source_id' => $sourceId,
                    'pipeline_stage_id' => $stageId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'preferred_name' => $firstName,
                    'phone' => '555-'.str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT),
                    'secondary_phone' => $index % 5 === 0 ? '555-'.str_pad((string) (2000 + $index), 4, '0', STR_PAD_LEFT) : null,
                    'city' => ['Vancouver', 'Toronto', 'Calgary', 'Seattle', 'Los Angeles'][$index % 5],
                    'state_province' => ['BC', 'ON', 'AB', 'WA', 'CA'][$index % 5],
                    'country' => $index % 4 === 0 ? 'United States' : 'Canada',
                    'timezone' => $index % 4 === 0 ? 'America/Los_Angeles' : 'America/Vancouver',
                    'preferred_language' => ['English', 'Spanish', 'Tagalog', 'French'][$index % 4],
                    'occupation' => ['Nurse', 'Business Owner', 'Engineer', 'Teacher', 'Realtor', 'Consultant'][$index % 6],
                    'employer_business' => ['Independent', 'Healthcare Group', 'Tech Studio', 'Real Estate Office'][$index % 4],
                    'date_of_birth' => now()->subYears(24 + ($index % 26))->subDays($index)->toDateString(),
                    'gender' => ['Female', 'Male', 'Prefer not to say'][$index % 3],
                    'marital_status' => ['Single', 'Married', 'Partnered'][$index % 3],
                    'children_count' => $index % 4,
                    'status' => $index % 12 === 0 ? 'archived' : 'active',
                    'interest_level' => $interestLevel,
                    'priority' => $priority,
                    'next_follow_up_at' => $nextFollowUp,
                    'last_contacted_at' => $lastContacted,
                    'appointment_at' => $index % 4 === 0 ? now()->addDays($index % 10)->setTime(14, 0) : null,
                    'conversion_at' => $index % 17 === 0 ? now()->subDays(2) : null,
                    'converted_to' => $index % 17 === 0 ? ($index % 2 === 0 ? 'client' : 'associate') : null,
                    'lost_reason' => $index % 13 === 0 ? 'Timing is not right yet.' : null,
                    'notes_summary' => 'Demo prospect for CRM workflow testing. Primary focus: '.$interestLevel.' interest and '.$priority.' priority.',
                    'is_client' => $index % 17 === 0,
                    'is_archived' => $index % 12 === 0,
                    'archived_at' => $index % 12 === 0 ? now()->subDays(5) : null,
                ]
            );

            $this->syncPivot('prospect_type_prospect', 'prospect_type_id', $prospect->id, $this->sliceIds($typeIds, $index, 2));
            $this->syncPivot('prospect_interest_prospect', 'prospect_interest_id', $prospect->id, $this->sliceIds($interestIds, $index, 3));
            $this->syncPivot('prospect_tag_pivot', 'prospect_tag_id', $prospect->id, $this->sliceIds($tagIds, $index, 2));

            $this->note($prospect, $owner->id, 'Initial conversation notes for '.$firstName.'. Need to clarify goals and timing.');
            $this->communication($prospect, $owner->id, $communicationTypeIds[$index % count($communicationTypeIds)] ?? null, $lastContacted, $index);
            $this->followUp($prospect, $owner->id, $nextFollowUp, $priority, $index);

            if ($index % 4 === 0) {
                $this->appointment($prospect, $owner->id, $cfm->id, $appointmentTypeIds[$index % count($appointmentTypeIds)] ?? null, $index);
            }

            if ($index % 7 === 0) {
                $this->share($prospect, $owner->id, $cfm->id, $sharePermissionId);
            }

            if ($index % 11 === 0) {
                $this->share($prospect, $owner->id, $trainer->id, $sharePermissionId, now()->addDays(30));
            }

            if ($prospect->converted_to) {
                $this->conversion($prospect, $owner->id);
            }
        }

        DB::table('prospect_imports')->updateOrInsert(
            ['user_id' => $owner->id, 'file_name' => 'demo-prospects.csv'],
            [
                'status' => 'completed',
                'total_rows' => 50,
                'imported_rows' => 50,
                'skipped_rows' => 0,
                'duplicate_rows' => 3,
                'preview_payload' => json_encode(['columns' => ['first_name', 'last_name', 'email', 'phone', 'tags']]),
                'duplicate_payload' => json_encode(['duplicates' => ['phone' => 2, 'email' => 1]]),
                'completed_at' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ]
        );
    }

    private function teamId(): int
    {
        DB::table('teams')->updateOrInsert(
            ['name' => 'Wealth Legacy Alliance'],
            [
                'description' => 'Default team for local development and seeded demo users.',
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return (int) DB::table('teams')->where('name', 'Wealth Legacy Alliance')->value('id');
    }

    private function user(string $email, string $name, string $role, ?int $rankId, int $teamId): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $rankId,
                'team_id' => $teamId,
                'is_active' => true,
                'joined_at' => now()->subMonths(3),
                'is_online' => false,
            ]
        );

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);

        return $user;
    }

    private function sliceIds(array $ids, int $offset, int $count): array
    {
        if ($ids === []) {
            return [];
        }

        return collect(range(0, $count - 1))
            ->map(fn (int $step) => $ids[($offset + $step) % count($ids)])
            ->unique()
            ->values()
            ->all();
    }

    private function syncPivot(string $table, string $column, string $prospectId, array $ids): void
    {
        DB::table($table)->where('prospect_id', $prospectId)->delete();

        foreach ($ids as $id) {
            DB::table($table)->insert([
                'prospect_id' => $prospectId,
                $column => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function note(Prospect $prospect, int $userId, string $note): void
    {
        DB::table('prospect_notes')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'user_id' => $userId, 'note' => $note],
            [
                'is_private' => false,
                'deleted_at' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]
        );
    }

    private function communication(Prospect $prospect, int $userId, ?int $typeId, $contactedAt, int $index): void
    {
        DB::table('prospect_communications')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'user_id' => $userId, 'contacted_at' => $contactedAt],
            [
                'communication_type_id' => $typeId,
                'direction' => $index % 5 === 0 ? 'inbound' : 'outbound',
                'outcome' => ['Connected', 'Left voicemail', 'No answer', 'Booked appointment'][$index % 4],
                'notes' => 'Seeded communication history for CRM timeline.',
                'next_action' => 'Follow up and confirm next step.',
                'next_follow_up_at' => $prospect->next_follow_up_at,
                'duration_minutes' => 5 + ($index % 20),
                'deleted_at' => null,
                'created_at' => $contactedAt,
                'updated_at' => now(),
            ]
        );
    }

    private function followUp(Prospect $prospect, int $userId, $dueAt, string $priority, int $index): void
    {
        DB::table('prospect_followups')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'assigned_user_id' => $userId, 'due_at' => $dueAt],
            [
                'followup_type' => ['Call', 'Text', 'Email', 'Zoom invite'][$index % 4],
                'priority' => $priority,
                'status' => $dueAt->isPast() ? 'overdue' : 'pending',
                'notes' => 'Seeded follow-up task for prospect center.',
                'completed_at' => null,
                'deleted_at' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ]
        );
    }

    private function appointment(Prospect $prospect, int $ownerId, int $helperId, ?int $typeId, int $index): void
    {
        DB::table('prospect_appointments')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'owner_id' => $ownerId, 'scheduled_at' => $prospect->appointment_at],
            [
                'assigned_helper_id' => $helperId,
                'appointment_type_id' => $typeId,
                'timezone' => $prospect->timezone,
                'location_or_link' => 'https://zoom.us/j/demo-'.$index,
                'purpose' => ['Needs analysis', 'Career overview', 'Protection review'][$index % 3],
                'status' => 'scheduled',
                'notes' => 'Seeded appointment for CRM calendar.',
                'reminder_status' => 'pending',
                'deleted_at' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ]
        );
    }

    private function share(Prospect $prospect, int $ownerId, int $sharedWith, ?int $permissionId, $expiresAt = null): void
    {
        DB::table('prospect_shares')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'shared_with' => $sharedWith, 'status' => 'active'],
            [
                'granted_by' => $ownerId,
                'prospect_share_permission_id' => $permissionId,
                'permission_level' => 'full_collaboration',
                'granted_at' => now()->subDays(2),
                'expires_at' => $expiresAt,
                'revoked_at' => null,
                'notes' => 'Seeded collaboration access.',
                'deleted_at' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]
        );
    }

    private function conversion(Prospect $prospect, int $userId): void
    {
        DB::table('prospect_conversions')->updateOrInsert(
            ['prospect_id' => $prospect->id, 'conversion_type' => $prospect->converted_to],
            [
                'converted_by' => $userId,
                'converted_at' => $prospect->conversion_at,
                'policy_reference' => $prospect->converted_to === 'client' ? 'POL-'.$prospect->id : null,
                'application_reference' => 'APP-'.$prospect->id,
                'notes' => 'Seeded conversion record.',
                'created_at' => $prospect->conversion_at,
                'updated_at' => now(),
            ]
        );
    }
}
