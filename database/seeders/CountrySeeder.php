<?php

namespace Database\Seeders;

use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 10;

        foreach (LocationOptions::countries() as $countryName) {
            DB::table('countries')->updateOrInsert(
                ['name' => $countryName],
                [
                    'code' => LocationOptions::countryDisplayCode($countryName),
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $sortOrder += 10;
        }
    }
}
