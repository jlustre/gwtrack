<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use App\Models\CalendarEventType;
use Illuminate\Database\Seeder;

class FnaLookupSeeder extends Seeder
{
    public function run(): void
    {
        $prospectsCategory = CalendarCategory::query()->where('slug', 'prospects')->first();

        if (! $prospectsCategory) {
            return;
        }

        foreach (config('fna.calendar_event_types', []) as $index => $type) {
            CalendarEventType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'calendar_category_id' => $prospectsCategory->id,
                    'name' => $type['name'],
                    'slug' => $type['slug'],
                    'color' => '#84BB41',
                    'sort_order' => 500 + (($index + 1) * 10),
                    'is_active' => true,
                ]
            );
        }
    }
}
