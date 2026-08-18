<?php

namespace Database\Seeders;

use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $countryIds = DB::table('countries')->pluck('id', 'name');

        foreach (LocationOptions::provincesByCountry() as $countryName => $provinces) {
            $countryId = $countryIds[$countryName] ?? null;

            if ($countryId === null) {
                continue;
            }

            $sortOrder = 10;

            foreach ($provinces as $provinceName => $provinceLabel) {
                $name = is_string($provinceLabel) ? $provinceLabel : $provinceName;

                DB::table('state_provinces')->updateOrInsert(
                    [
                        'country_id' => $countryId,
                        'name' => $name,
                    ],
                    [
                        'code' => LocationOptions::provinceDisplayCode($countryName, $name),
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
}
