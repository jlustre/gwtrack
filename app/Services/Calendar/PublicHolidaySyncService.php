<?php

namespace App\Services\Calendar;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Support\SystemTaskAssignor;
use Illuminate\Support\Facades\Cache;

class PublicHolidaySyncService
{
    public function __construct(private readonly PublicHolidayCatalog $catalog) {}

    public function ensureRollingWindow(?int $anchorYear = null): void
    {
        $years = $this->catalog->yearsForRollingWindow($anchorYear);
        $cacheKey = 'public-holidays.synced-through.'.max($years);

        if (Cache::get($cacheKey) === true) {
            return;
        }

        $this->syncYears($years);

        Cache::put($cacheKey, true, now()->addDay());
    }

    /**
     * @param  list<int>  $years
     */
    public function syncYears(array $years): void
    {
        $organizerId = SystemTaskAssignor::USER_ID;

        foreach ($this->catalog->calendarDefinitions() as $slug => $definition) {
            $category = CalendarCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'],
                    'icon' => $definition['icon'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );

            $eventType = CalendarEventType::updateOrCreate(
                ['slug' => $definition['event_type']['slug']],
                [
                    'calendar_category_id' => $category->id,
                    'name' => $definition['event_type']['name'],
                    'color' => $definition['color'],
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );

            foreach ($years as $year) {
                foreach ($this->catalog->holidaysForCalendar($slug, $year) as $holiday) {
                    $startsAt = $holiday['date']->startOfDay();
                    $endsAt = $holiday['date']->endOfDay();

                    CalendarEvent::updateOrCreate(
                        [
                            'calendar_category_id' => $category->id,
                            'title' => $holiday['name'],
                            'starts_at' => $startsAt,
                        ],
                        [
                            'calendar_event_type_id' => $eventType->id,
                            'organizer_id' => $organizerId,
                            'description' => $category->name.' observance.',
                            'ends_at' => $endsAt,
                            'timezone' => $definition['timezone'] ?? 'UTC',
                            'is_all_day' => true,
                            'is_recurring' => false,
                            'visibility' => 'public_organization',
                            'status' => 'scheduled',
                            'color' => $category->color,
                        ]
                    );
                }
            }
        }
    }
}
