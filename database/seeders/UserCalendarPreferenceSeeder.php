<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use App\Models\User;
use App\Models\UserCalendarPreference;
use Illuminate\Database\Seeder;

class UserCalendarPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $personalId = CalendarCategory::query()
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->value('id');

        $defaultCalendarIds = $personalId ? [(int) $personalId] : [];

        User::query()
            ->whereNull('deleted_at')
            ->chunkById(100, function ($users) use ($defaultCalendarIds): void {
                foreach ($users as $user) {
                    UserCalendarPreference::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'default_view' => 'month',
                            'timezone' => $user->profile?->timezone ?? 'America/Vancouver',
                            'visible_calendar_categories' => $defaultCalendarIds,
                            'show_weekends' => true,
                        ]
                    );
                }
            });
    }
}
