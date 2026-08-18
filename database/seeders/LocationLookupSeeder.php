<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocationLookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);
    }
}
