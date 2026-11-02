<?php

namespace Tests\Feature;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\PublicHolidayCatalog;
use App\Services\Calendar\PublicHolidaySyncService;
use Carbon\CarbonImmutable;
use Database\Seeders\PublicHolidayCalendarSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemTaskAssignorSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicHolidayCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_holiday_seeder_creates_us_and_canada_calendars_with_events(): void
    {
        $this->seedPublicHolidays();

        $usCategory = CalendarCategory::where('slug', PublicHolidayCatalog::US_CATEGORY_SLUG)->firstOrFail();
        $canadaCategory = CalendarCategory::where('slug', PublicHolidayCatalog::CANADA_CATEGORY_SLUG)->firstOrFail();

        $this->assertGreaterThan(0, CalendarEvent::where('calendar_category_id', $usCategory->id)->count());
        $this->assertGreaterThan(0, CalendarEvent::where('calendar_category_id', $canadaCategory->id)->count());

        $this->assertDatabaseHas('calendar_events', [
            'calendar_category_id' => $usCategory->id,
            'title' => 'Independence Day',
            'is_all_day' => true,
            'visibility' => 'public_organization',
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'calendar_category_id' => $canadaCategory->id,
            'title' => 'Canada Day',
            'is_all_day' => true,
            'visibility' => 'public_organization',
        ]);
    }

    public function test_calendar_page_shows_public_holiday_sidebar_and_events(): void
    {
        $this->seedPublicHolidays();

        $user = User::where('email', 'super-admin@gwtracks.com')->firstOrFail();
        $year = now()->year;

        CalendarEvent::where('title', 'Independence Day')
            ->whereYear('starts_at', $year)
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('calendar.index', ['date' => "{$year}-07-01", 'view' => 'month']))
            ->assertOk()
            ->assertSee('Public Holidays', false)
            ->assertSee('US Public Holidays', false)
            ->assertSee('Canadian Public Holidays', false)
            ->assertSee('Independence Day', false)
            ->assertSee('Canada Day', false);
    }

    public function test_catalog_resolves_floating_holidays_for_future_years(): void
    {
        $catalog = app(PublicHolidayCatalog::class);

        $thanksgiving2027 = $catalog->holidaysForCalendar(PublicHolidayCatalog::US_CATEGORY_SLUG, 2027)
            ->firstWhere('name', 'Thanksgiving Day');

        $this->assertNotNull($thanksgiving2027);
        $this->assertTrue($thanksgiving2027['date']->isSameDay(CarbonImmutable::create(2027, 11, 25)));

        $independence2027 = $catalog->holidaysForCalendar(PublicHolidayCatalog::US_CATEGORY_SLUG, 2027)
            ->firstWhere('name', 'Independence Day');

        $this->assertNotNull($independence2027);
        $this->assertTrue($independence2027['date']->isSameDay(CarbonImmutable::create(2027, 7, 5)));
    }

    public function test_sync_generates_events_for_configured_year_window(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            SystemTaskAssignorSeeder::class,
            UsersSeeder::class,
        ]);

        $catalog = app(PublicHolidayCatalog::class);
        $sync = app(PublicHolidaySyncService::class);
        $years = $catalog->yearsForRollingWindow(2026);

        $sync->syncYears($years);

        $usCategory = CalendarCategory::where('slug', PublicHolidayCatalog::US_CATEGORY_SLUG)->firstOrFail();

        foreach ($years as $year) {
            $this->assertDatabaseHas('calendar_events', [
                'calendar_category_id' => $usCategory->id,
                'title' => 'Thanksgiving Day',
            ]);

            $this->assertTrue(
                CalendarEvent::where('calendar_category_id', $usCategory->id)
                    ->where('title', 'Thanksgiving Day')
                    ->whereYear('starts_at', $year)
                    ->exists()
            );
        }
    }

    public function test_ensure_rolling_window_is_idempotent_with_cache(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            SystemTaskAssignorSeeder::class,
            UsersSeeder::class,
        ]);

        $sync = app(PublicHolidaySyncService::class);
        $catalog = app(PublicHolidayCatalog::class);
        $years = $catalog->yearsForRollingWindow();
        $cacheKey = 'public-holidays.synced-through.'.max($years);

        Cache::forget($cacheKey);

        $sync->ensureRollingWindow();
        $countAfterFirst = CalendarEvent::count();

        $sync->ensureRollingWindow();
        $countAfterSecond = CalendarEvent::count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
        $this->assertTrue(Cache::get($cacheKey) === true);
    }

    public function test_calendar_view_triggers_holiday_sync_for_viewed_year(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            SystemTaskAssignorSeeder::class,
            UsersSeeder::class,
        ]);

        $user = User::where('email', 'super-admin@gwtracks.com')->firstOrFail();
        $viewYear = now()->year + 2;

        $this->assertFalse(
            CalendarEvent::where('title', 'Independence Day')->whereYear('starts_at', $viewYear)->exists()
        );

        $this->actingAs($user)
            ->get(route('calendar.index', ['date' => "{$viewYear}-07-01", 'view' => 'month']))
            ->assertOk();

        $this->assertTrue(
            CalendarEvent::where('title', 'Independence Day')->whereYear('starts_at', $viewYear)->exists()
        );
    }

    private function seedPublicHolidays(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            SystemTaskAssignorSeeder::class,
            UsersSeeder::class,
            PublicHolidayCalendarSeeder::class,
        ]);
    }
}
