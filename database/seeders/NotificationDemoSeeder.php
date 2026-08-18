<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use NotificationsSeeder instead.
 */
class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NotificationsSeeder::class);
    }
}
