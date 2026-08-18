<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('teams')->updateOrInsert(
            ['name' => 'Wealth Legacy Alliance'],
            [
                'description' => 'Default team for all users.',
                'leader_id' => null,
                'owner_id' => null,
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
