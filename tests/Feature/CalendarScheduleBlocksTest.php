<?php

namespace Tests\Feature;

use App\Models\CalendarScheduleBlock;
use App\Models\CalendarScheduleBlockOverride;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CalendarScheduleBlockService;
use Carbon\CarbonImmutable;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarScheduleBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_weekly_schedule_block_from_settings(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('calendar.schedule-blocks.store'), [
                'block_type' => 'work',
                'label' => 'Day Job',
                'weekday' => 1,
                'starts_at' => '09:00',
                'ends_at' => '17:00',
                'is_shared' => '1',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('calendar_schedule_blocks', [
            'user_id' => $user->id,
            'block_type' => 'work',
            'label' => 'Day Job',
            'weekday' => 1,
            'is_shared' => true,
        ]);
    }

    public function test_cfm_can_see_trainee_shared_schedule_blocks_on_calendar(): void
    {
        $this->seedCalendar();

        $cfm = User::where('email', 'calendar-cfm@gwtrack.com')->firstOrFail();
        $trainee = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $trainee->update(['mentor_id' => $cfm->id]);

        MentorAssignment::query()->updateOrCreate(
            ['mentor_id' => $cfm->id, 'apprentice_id' => $trainee->id, 'status' => 'active'],
            ['assigned_by' => $cfm->id, 'started_at' => now()->subDays(5)]
        );

        CalendarScheduleBlock::create([
            'user_id' => $trainee->id,
            'block_type' => 'work',
            'label' => 'Trainee Work Shift',
            'weekday' => now()->dayOfWeekIso,
            'starts_at' => '08:00',
            'ends_at' => '16:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($cfm)
            ->get(route('calendar.week', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Trainee Work Shift', false)
            ->assertSee('Shared Availability', false);
    }

    public function test_trainee_cannot_see_private_schedule_blocks_from_other_users(): void
    {
        $this->seedCalendar();

        $cfm = User::where('email', 'calendar-cfm@gwtrack.com')->firstOrFail();
        $trainee = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $other = User::where('email', 'calendar-leader@gwtrack.com')->firstOrFail();

        CalendarScheduleBlock::create([
            'user_id' => $other->id,
            'block_type' => 'personal',
            'label' => 'Secret Personal Time',
            'weekday' => now()->dayOfWeekIso,
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($trainee)
            ->get(route('calendar.week', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee('Secret Personal Time');
    }

    public function test_member_can_add_weekly_block_spanning_multiple_days(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('calendar.schedule-blocks.store'), [
                'block_type' => 'personal',
                'label' => 'Weekend Trip',
                'weekday' => 5,
                'end_weekday' => 6,
                'starts_at' => '18:00',
                'ends_at' => '20:00',
                'is_shared' => '1',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('calendar_schedule_blocks', [
            'user_id' => $user->id,
            'weekday' => 5,
            'end_weekday' => 6,
            'label' => 'Weekend Trip',
        ]);
    }

    public function test_weekly_block_spanning_days_appears_on_calendar(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $friday = now()->startOfWeek()->addDays(4);

        CalendarScheduleBlock::create([
            'user_id' => $user->id,
            'block_type' => 'personal',
            'label' => 'Fri-Sat Block',
            'weekday' => 5,
            'end_weekday' => 6,
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->get(route('calendar.week', ['date' => $friday->toDateString()]))
            ->assertOk()
            ->assertSee('Fri-Sat Block', false)
            ->assertSee('6:00 PM', false)
            ->assertSee('8:00 PM', false);
    }

    public function test_weekly_block_spanning_days_uses_same_range_display_as_date_overrides(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $friday = CarbonImmutable::parse(now()->startOfWeek()->addDays(4)->toDateString());

        CalendarScheduleBlock::create([
            'user_id' => $user->id,
            'block_type' => 'personal',
            'label' => 'Fri-Sat Block',
            'weekday' => 5,
            'end_weekday' => 6,
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        /** @var CalendarScheduleBlockService $service */
        $service = app(CalendarScheduleBlockService::class);
        $expanded = $service->expandedBlocksByDate($user, $friday, $friday->addDay());

        $fridayBlock = $expanded[$friday->toDateString()]->first();
        $saturdayBlock = $expanded[$friday->copy()->addDay()->toDateString()]->first();

        $this->assertSame('start', $fridayBlock['span_position']);
        $this->assertSame('end', $saturdayBlock['span_position']);
        $this->assertSame($fridayBlock['range_key'], $saturdayBlock['range_key']);
        $this->assertStringContainsString('Fri', $fridayBlock['time_label']);
        $this->assertStringContainsString('Sat', $fridayBlock['time_label']);
        $this->assertStringContainsString('6:00 PM', $fridayBlock['time_label']);
        $this->assertStringContainsString('8:00 PM', $fridayBlock['time_label']);
    }

    public function test_member_can_add_multi_day_all_day_date_range(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $start = now()->addWeek()->toDateString();
        $end = now()->addWeeks(2)->toDateString();

        $this->actingAs($user)
            ->post(route('calendar.schedule-block-overrides.store'), [
                'block_date' => $start,
                'block_end_date' => $end,
                'block_type' => 'personal',
                'label' => 'Vacation',
                'is_all_day' => '1',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $override = CalendarScheduleBlockOverride::query()
            ->where('user_id', $user->id)
            ->where('label', 'Vacation')
            ->firstOrFail();

        $this->assertTrue($override->is_all_day);
        $this->assertSame($start, $override->block_date->toDateString());
        $this->assertSame($end, $override->block_end_date->toDateString());
    }

    public function test_member_can_add_timed_date_range_spanning_days(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();
        $friday = now()->next('Friday');

        $this->actingAs($user)
            ->post(route('calendar.schedule-block-overrides.store'), [
                'block_date' => $friday->toDateString(),
                'block_end_date' => $friday->copy()->addDay()->toDateString(),
                'block_type' => 'personal',
                'label' => 'Weekend Away',
                'starts_at' => '18:00',
                'ends_at' => '20:00',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $this->actingAs($user)
            ->get(route('calendar.week', ['date' => $friday->toDateString()]))
            ->assertOk()
            ->assertSee('Weekend Away', false);
    }

    public function test_settings_page_does_not_show_schedule_sharing_preferences(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('calendar.settings'))
            ->assertOk()
            ->assertDontSee('Share Blocked Times', false)
            ->assertDontSee('Save Sharing Preferences', false)
            ->assertSee('Weekly Recurring Blocks', false);
    }

    public function test_member_can_update_weekly_schedule_block_from_settings(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $block = CalendarScheduleBlock::create([
            'user_id' => $user->id,
            'block_type' => 'work',
            'label' => 'Day Job',
            'weekday' => 2,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('calendar.schedule-blocks.update', $block), [
                'block_type' => 'personal',
                'label' => 'Evening Class',
                'weekday' => 4,
                'starts_at' => '18:00',
                'ends_at' => '20:00',
                'is_shared' => '1',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $block->refresh();

        $this->assertSame('personal', $block->block_type);
        $this->assertSame('Evening Class', $block->label);
        $this->assertSame(4, $block->weekday);
        $this->assertSame('18:00', substr((string) $block->starts_at, 0, 5));
        $this->assertSame('20:00', substr((string) $block->ends_at, 0, 5));
    }

    public function test_member_can_update_date_block_override_from_settings(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        $override = CalendarScheduleBlockOverride::create([
            'user_id' => $user->id,
            'block_date' => now()->addWeek()->toDateString(),
            'block_type' => 'other',
            'label' => 'Doctor Visit',
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'is_all_day' => false,
            'is_blocked' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('calendar.schedule-block-overrides.update', $override), [
                'block_date' => now()->addWeeks(2)->toDateString(),
                'block_type' => 'personal',
                'label' => 'Vacation Day',
                'reason' => 'Family trip',
                'is_all_day' => '1',
                'return_to' => route('calendar.settings'),
            ])
            ->assertRedirect(route('calendar.settings'))
            ->assertSessionHas('status');

        $override->refresh();

        $this->assertSame('personal', $override->block_type);
        $this->assertSame('Vacation Day', $override->label);
        $this->assertSame('Family trip', $override->reason);
        $this->assertTrue($override->is_all_day);
        $this->assertNull($override->starts_at);
        $this->assertNull($override->ends_at);
    }

    public function test_settings_page_shows_view_edit_buttons_for_blocks(): void
    {
        $this->seedCalendar();

        $user = User::where('email', 'calendar-member@gwtrack.com')->firstOrFail();

        CalendarScheduleBlock::create([
            'user_id' => $user->id,
            'block_type' => 'work',
            'label' => 'Day Job',
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'is_active' => true,
            'is_shared' => true,
        ]);

        CalendarScheduleBlockOverride::create([
            'user_id' => $user->id,
            'block_date' => now()->addDays(3)->toDateString(),
            'block_type' => 'personal',
            'label' => 'Appointment',
            'is_all_day' => true,
            'is_blocked' => true,
            'is_shared' => true,
        ]);

        $this->actingAs($user)
            ->get(route('calendar.settings'))
            ->assertOk()
            ->assertSee('View / Edit', false)
            ->assertSee('View / Edit Details', false);
    }

    private function seedCalendar(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
        ]);
    }
}
