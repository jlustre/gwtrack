<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            ['code' => 'FA', 'name' => 'Field Associate'],
            ['code' => 'SFA', 'name' => 'Senior Field Associate'],
            ['code' => 'SM', 'name' => 'Sales Manager'],
            ['code' => 'ED', 'name' => 'Executive Director'],
            ['code' => 'SED', 'name' => 'Senior Executive Director'],
            ['code' => 'NED', 'name' => 'National Executive Director'],
            ['code' => 'SNED', 'name' => 'Senior National Executive Director'],
            ['code' => 'EP', 'name' => 'Executive Partner'],
        ];

        foreach ($ranks as $index => $rank) {
            Rank::updateOrCreate(
                ['code' => $rank['code']],
                [
                    'name' => $rank['name'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
