<?php

namespace Database\Seeders;

use App\Services\Calendar\PublicHolidayCatalog;
use App\Services\Calendar\PublicHolidaySyncService;
use Illuminate\Database\Seeder;

class PublicHolidayCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(PublicHolidayCatalog::class);
        $sync = app(PublicHolidaySyncService::class);

        $sync->syncYears($catalog->yearsForRollingWindow());
    }
}
