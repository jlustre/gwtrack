<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NotificationModuleSeeder::class);
        $this->call(NotificationPushSmsEnhancementSeeder::class);
        $this->call(NotificationEscalationSeeder::class);
    }
}
