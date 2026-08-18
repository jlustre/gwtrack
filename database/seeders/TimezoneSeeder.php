<?php

namespace Database\Seeders;

use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimezoneSeeder extends Seeder
{
    /**
     * @return array<string, string|null>
     */
    private function timezoneCountries(): array
    {
        return [
            'PST' => null,
            'MST' => null,
            'CST' => null,
            'EST' => null,
            'HST' => null,
            'Halifax Time' => 'Canada',
            'Canada Pacific Time' => 'Canada',
            'Canada Mountain Time' => 'Canada',
            'Canada Central Time' => 'Canada',
            'Canada Eastern Time' => 'Canada',
            'Philippines Time' => 'Philippines',
            'Mexico Pacific Time' => 'Mexico',
            'Mexico Mountain Time' => 'Mexico',
            'Mexico Central Time' => 'Mexico',
            'Mexico Eastern Time' => 'Mexico',
        ];
    }

    public function run(): void
    {
        $countryIds = DB::table('countries')->pluck('id', 'name');
        $sortOrder = 10;

        foreach (LocationOptions::timezones() as $code => $label) {
            $countryName = $this->timezoneCountries()[$code] ?? null;
            $countryId = $countryName ? ($countryIds[$countryName] ?? null) : null;

            DB::table('timezones')->updateOrInsert(
                ['code' => $code],
                [
                    'country_id' => $countryId,
                    'name' => $label,
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
