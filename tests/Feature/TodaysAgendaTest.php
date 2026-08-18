<?php

namespace Tests\Feature;

use App\Models\ProfileCompletionField;
use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\ScheduledPhoneCall;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\TodaysAgendaService;
use App\Support\TaskUserAttributes;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TodaysAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_bar_shows_todays_agenda_button(): void
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

    public function test_service_groups_today_items_by_category(): void
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

        $meetingTypeId = CalendarEventType::query()->where('slug', 'team-meeting')->value('id');
        $discoveryTypeId = (int) DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

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

        ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'prospect',
            'prospect_id' => $prospect->id,
            'contact_name' => 'Call Today',
            'scheduled_at' => now()->setTime(10, 0),
            'timezone' => 'America/Vancouver',
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

        $agenda = app(TodaysAgendaService::class)->forUser($user);
        $counts = collect($agenda['sections'])->pluck('count', 'key');

        $this->assertGreaterThanOrEqual(1, (int) ($counts['tasks'] ?? 0));
        $this->assertGreaterThanOrEqual(1, (int) ($counts['phone_calls'] ?? 0));
        $this->assertGreaterThanOrEqual(1, (int) ($counts['appointments'] ?? 0));
        $this->assertGreaterThanOrEqual(1, (int) ($counts['events'] ?? 0));
        $this->assertTrue($agenda['has_items']);
        $this->assertGreaterThanOrEqual(4, $agenda['total']);
    }

    public function test_login_opens_todays_agenda_when_items_exist(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create([
            'email' => 'agenda@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        ProfileCompletionField::query()->delete();

        TaskUser::query()->create(TaskUserAttributes::forTask(
            'Personal',
            'Finish onboarding packet',
            [
                'assignee_id' => $user->id,
                'assignor_id' => $user->id,
                'priority' => 'medium',
                'status' => 'to_do',
                'related_module' => 'profile',
                'due_date' => now()->toDateString(),
            ],
        ));

        $this->assertTrue(app(TodaysAgendaService::class)->summaryFor($user)['has_items']);
        $this->assertTrue(app(\App\Services\ProfileCompletionService::class)->isComplete($user));

        $this->post('/login', [
            'email' => 'agenda@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('show_todays_agenda_modal');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Open today's agenda", false);
    }

    public function test_modal_lists_open_tasks_not_only_due_today(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        TaskUser::query()->create(TaskUserAttributes::forTask(
            'Personal',
            'Finish onboarding packet',
            [
                'assignee_id' => $user->id,
                'assignor_id' => $user->id,
                'priority' => 'medium',
                'status' => 'to_do',
                'related_module' => 'profile',
                'due_date' => now()->addWeek()->toDateString(),
            ],
        ));

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\TodaysAgendaModal::class)
            ->call('open')
            ->assertSee('Finish onboarding packet', false);
    }

    public function test_modal_sections_can_be_collapsed_and_expanded(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\TodaysAgendaModal::class)
            ->call('open')
            ->assertSet('collapsedSections.tasks', true)
            ->call('toggleSection', 'tasks')
            ->assertSet('collapsedSections.tasks', false)
            ->call('collapseAllSections')
            ->assertSet('collapsedSections.tasks', true)
            ->call('expandAllSections')
            ->assertSet('collapsedSections.tasks', false);
    }

    public function test_modal_lists_task_items(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        TaskUser::query()->create(TaskUserAttributes::forTask(
            'Personal',
            'Finish onboarding packet',
            [
                'assignee_id' => $user->id,
                'assignor_id' => $user->id,
                'priority' => 'medium',
                'status' => 'to_do',
                'related_module' => 'profile',
                'due_date' => now()->toDateString(),
            ],
        ));

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\TodaysAgendaModal::class)
            ->call('open')
            ->assertSee("Today's Agenda", false)
            ->assertSee('Finish onboarding packet', false)
            ->assertSee('Tasks', false);
    }
}
