<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Core login accounts only. Sponsorship genealogy lives in DownlineManagementSeeder.
 */
class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $smbRankId = Rank::where('code', 'SMB')->value('id');
        $svpRankId = Rank::where('code', 'SVP')->value('id');
        $saRankId = Rank::where('code', 'SA')->value('id');

        $superAdmin = User::updateOrCreate(
            ['email' => 'super-admin@gwtrack.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password123'),
                'joined_at' => now(),
                'sponsor_id' => null,
                'mentor_id' => null,
                'rank_id' => $smbRankId,
                'team_id' => 1,
                'is_online' => false,
                'is_active' => true,
                'last_login_at' => today(),
                'email_verified_at' => today(),
                'created_at' => today(),
                'updated_at' => today(),
            ]
        );

        $superAdmin->syncRoles([
            'super-admin',
            'agency-owner',
            'certified-field-mentor',
        ]);

        // $teamId = DB::table('teams')->where('name', 'GW Team')->value('id');
        $teamId = 1; // Assuming the team ID is 1 for the default team created by TeamSeeder

        if ($teamId) {
            $superAdmin->forceFill([
                'team_id' => $teamId,
                'sponsor_id' => 1,
                'mentor_id' => $superAdmin->id,
            ])->save();

            $agencyOwner = User::updateOrCreate(
                ['email' => 'agency-owner@gwtrack.com'],
                [
                    'name' => 'Mae Tsvetkoff',
                    'password' => Hash::make('Password123'),
                    'joined_at' => now(),
                    'sponsor_id' => $superAdmin->id,
                    'mentor_id' => $superAdmin->id,
                    'rank_id' => $svpRankId,
                    'team_id' => $teamId,
                    'is_online' => false,
                    'is_active' => true,
                    'last_login_at' => today(),
                    'email_verified_at' => today(),
                    'created_at' => today(),
                    'updated_at' => today(),
                ]
            );

            $agencyOwner->syncRoles([
                'agency-owner',
            ]);

            $cfmUser = User::updateOrCreate(
                ['email' => 'cfm@gwtrack.com'],
                [
                    'name' => 'CFM User',
                    'password' => Hash::make('Password123'),
                    'joined_at' => now(),
                    'sponsor_id' => $agencyOwner->id,
                    'mentor_id' => $superAdmin->id,
                    'rank_id' => $saRankId,
                    'team_id' => $teamId,
                    'is_online' => false,
                    'is_active' => true,
                    'last_login_at' => today(),
                    'email_verified_at' => today(),
                    'created_at' => today(),
                    'updated_at' => today(),
                ]
            );

            $cfmUser->syncRoles([
                'certified-field-mentor',
            ]);

            DB::table('teams')->where('id', $teamId)->update([
                'owner_id' => $superAdmin->id,
                'leader_id' => $superAdmin->id,
                'updated_at' => now(),
            ]);
        }

        app(DownlineHierarchyService::class)->rebuild();
    }
}
