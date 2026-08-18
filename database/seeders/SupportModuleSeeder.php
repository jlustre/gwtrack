<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SupportSlaPolicy;
use App\Models\SupportTicketStatus;
use Illuminate\Database\Seeder;

class SupportModuleSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'New', 'slug' => 'new', 'color_hex' => '#60A5FA', 'is_system_default' => true, 'sort_order' => 10],
            ['name' => 'Open', 'slug' => 'open', 'color_hex' => '#38BDF8', 'is_system_default' => false, 'sort_order' => 20],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'color_hex' => '#84BB41', 'is_system_default' => false, 'sort_order' => 30],
            ['name' => 'Awaiting User', 'slug' => 'awaiting_user', 'color_hex' => '#F59E0B', 'is_system_default' => false, 'sort_order' => 40],
            ['name' => 'Under Review', 'slug' => 'under_review', 'color_hex' => '#A78BFA', 'is_system_default' => false, 'sort_order' => 50],
            ['name' => 'Resolved', 'slug' => 'resolved', 'color_hex' => '#34D399', 'is_system_default' => false, 'sort_order' => 60],
            ['name' => 'Closed', 'slug' => 'closed', 'color_hex' => '#94A3B8', 'is_system_default' => false, 'sort_order' => 70],
        ];

        foreach ($statuses as $status) {
            SupportTicketStatus::query()->updateOrCreate(
                ['slug' => $status['slug']],
                $status,
            );
        }

        $policies = [
            ['urgency' => 'urgent', 'response_time_hours' => 4],
            ['urgency' => 'high', 'response_time_hours' => 8],
            ['urgency' => 'medium', 'response_time_hours' => 24],
            ['urgency' => 'low', 'response_time_hours' => 72],
        ];

        foreach ($policies as $policy) {
            SupportSlaPolicy::query()->updateOrCreate(
                ['urgency' => $policy['urgency']],
                $policy,
            );
        }
    }
}
