<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds curated prospect CRM data for manual dashboard testing.
 *
 *   php artisan db:seed --class=ProspectDashboardTestSeeder
 *
 * Primary login: prospects@gwtracks.com / Password123
 * Start at: /team/prospects
 */
class ProspectDashboardTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            ProspectModuleTestSeeder::class,
        ]);

        $this->command?->info('Prospect dashboard test data ready.');
        $this->command?->info('Login: prospects@gwtracks.com / Password123');
        $this->command?->info('URL: '.url('/team/prospects'));
    }
}
