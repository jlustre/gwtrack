<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\TodayScheduleSummaryService;
use App\Support\TaskUserAttributes;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TodayScheduleSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_bar_shows_today_schedule_dropdown(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Open today's agenda", false);
    }

    public function test_service_counts_today_calendar_events_and_tasks_by_bucket(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $discoveryTypeId = (int) DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');
        $presentationTypeId = CalendarEventType::query()->where('slug', 'recruiting-presentation')->value('id');
        $meetingTypeId = CalendarEventType::query()->where('slug', 'team-meeting')->value('id');

        CalendarEvent::query()->create([
            'calendar_event_type_id' => $presentationTypeId,
            'organizer_id' => $user->id,
            'title' => 'Presentation today',
            'starts_at' => now()->setTime(11, 0),
            'ends_at' => now()->setTime(12, 0),
            'timezone' => 'America/Vancouver',
            'visibility' => 'private',
            'status' => 'scheduled',
        ]);

        CalendarEvent::query()->create([
            'calendar_event_type_id' => $meetingTypeId,
            'organizer_id' => $user->id,
            'title' => 'Team huddle',
            'starts_at' => now()->setTime(15, 0),
            'ends_at' => now()->setTime(15, 30),
            'timezone' => 'America/Vancouver',
            'visibility' => 'private',
            'status' => 'scheduled',
        ]);

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Call',
            'last_name' => 'Today',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        ProspectAppointment::query()->create([
            'prospect_id' => $prospect->id,
            'owner_id' => $user->id,
            'appointment_type_id' => $discoveryTypeId,
            'scheduled_at' => now()->setTime(9, 30),
            'status' => 'scheduled',
        ]);

        TaskUser::query()->create(TaskUserAttributes::forTask(
            'Prospect Follow-Up',
            'Follow up today',
            [
                'assignee_id' => $user->id,
                'assignor_id' => $user->id,
                'priority' => 'medium',
                'status' => 'to_do',
                'related_module' => 'prospects',
                'due_date' => now()->toDateString(),
            ],
        ));

        $summary = app(TodayScheduleSummaryService::class)->forUser($user);

        $counts = collect($summary['buckets'])->pluck('count', 'key');

        $this->assertSame(1, (int) ($counts['phone_calls'] ?? 0));
        $this->assertSame(1, (int) ($counts['presentations'] ?? 0));
        $this->assertSame(1, (int) ($counts['meetings'] ?? 0));
        $this->assertSame(1, $summary['tasks']['count']);
        $this->assertSame(4, $summary['total']);
    }

    public function test_dashboard_loads_when_profile_uses_app_timezone_label(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->profile()->create([
            'timezone_id' => \App\Support\LocationOptions::resolveTimezoneId('Canada Pacific Time'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Open today's agenda", false);

        $summary = app(TodayScheduleSummaryService::class)->forUser($user->fresh('profile.timezoneRecord'));

        $this->assertNotSame('', $summary['date_label']);
    }
}
