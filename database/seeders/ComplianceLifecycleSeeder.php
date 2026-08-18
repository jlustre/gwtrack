<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplianceLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNotificationTrigger();
    }

    private function seedNotificationTrigger(): void
    {
        $triggerCode = config('compliance-lifecycle.notification_trigger', 'compliance_renewal_due');

        if (DB::table('notification_triggers')->where('code', $triggerCode)->exists()) {
            return;
        }

        $typeId = DB::table('notification_types')
            ->where('code', 'licensing')
            ->value('id');

        if ($typeId === null) {
            return;
        }

        DB::table('notification_triggers')->insert([
            'notification_type_id' => $typeId,
            'code' => $triggerCode,
            'name' => 'Compliance Renewal Due',
            'description' => 'Sent when a license, E&O, AML, CE, or carrier appointment is approaching expiration.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
