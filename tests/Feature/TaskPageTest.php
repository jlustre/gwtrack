<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskController;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\NewMemberOnboardingTaskService;
use App\Support\TaskUserAttributes;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_page_shows_confirmation_assignment_and_email_tasks(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            TimezoneSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $member = User::factory()->create([
            'sponsor_id' => $agencyOwner->id,
            'mentor_id' => null,
        ]);
        $member->assignRole('member');
        $member->profile()->create(array_merge([
            'is_gw_active_associate' => true,
            'gw_associate_id' => 'GW-TASK-1',
        ], LocationOptions::profileLocationIds('Canada')));

        $stepId = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'onboarding')
            ->where('checklists.title', 'Complete Member Profile')
            ->value('checklists.id');

        DB::table('checklist_progress')->insert([
            'user_id' => $member->id,
            'checklist_id' => $stepId,
            'mentor_assignment_id' => null,
            'status' => 'pending_confirmation',
            'submitted_at' => now()->subDay(),
            'completed_at' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('registration_invitations')->insert([
            'sponsor_id' => $agencyOwner->id,
            'code' => Str::upper(Str::random(12)),
            'email' => 'prospect@example.com',
            'role_name' => 'member',
            'max_uses' => 1,
            'uses_count' => 0,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->actingAs($agencyOwner)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Organize team priorities')
            ->assertSee('Complete Member Profile')
            ->assertSee('Add confirmation notes')
            ->assertSee('Reject')
            ->assertSee('Confirm')
            ->assertSee('Assign a CFM to '.$member->name)
            ->assertSee('Send invitation email')
            ->assertSee('prospect@example.com');
    }

    public function test_invitation_email_tasks_in_my_tasks_open_invite_member_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $sponsor->assignRole('agency-owner');

        $invitationId = DB::table('registration_invitations')->insertGetId([
            'sponsor_id' => $sponsor->id,
            'code' => Str::upper(Str::random(12)),
            'email' => 'pending.recruit.invite@example.com',
            'role_name' => 'member',
            'max_uses' => 1,
            'uses_count' => 0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $payload = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $emailTask = collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        );

        $this->assertNotNull($emailTask);
        $this->assertSame('Member Invitations', $emailTask['category']);
        $this->assertSame('Send invitation email', $emailTask['action_label']);
        $this->assertSame('open-invite-member-modal', $emailTask['action_event']);
        $this->assertSame($invitationId, $emailTask['invitation_id']);
        $this->assertSame(['invitationId' => $invitationId], $emailTask['action_event_params']);
        $this->assertNull($emailTask['url']);
        $this->assertStringContainsString('pending.recruit.invite@example.com', (string) ($emailTask['notes'] ?? ''));
        $this->assertFalse($emailTask['completable'] ?? true);
    }

    public function test_expired_invitation_does_not_create_send_email_task(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $sponsor->assignRole('agency-owner');

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'expired.recruit@example.com',
            'expires_at' => now()->subDay(),
            'last_emailed_at' => null,
        ]);

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'active.recruit@example.com',
            'expires_at' => now()->addWeek(),
            'last_emailed_at' => null,
        ]);

        $payload = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $emailTasks = collect($payload['items'])->filter(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        );

        $this->assertCount(1, $emailTasks);
        $this->assertStringContainsString('active.recruit@example.com', (string) ($emailTasks->first()['notes'] ?? ''));
    }

    public function test_revoked_invitation_does_not_create_send_email_task(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $sponsor->assignRole('agency-owner');

        $invitation = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'deleted.recruit@example.com',
            'last_emailed_at' => null,
        ]);

        app(\App\Services\Members\MemberInvitationService::class)->revokeInvitation($sponsor, $invitation);

        $payload = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $this->assertNull(collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_accepted_invitation_does_not_create_send_email_task(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $sponsor->assignRole('agency-owner');

        $registeredMember = User::factory()->create(['email' => 'registered.recruit@example.com']);

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'registered.recruit@example.com',
            'accepted_by' => $registeredMember->id,
            'accepted_at' => now(),
            'revoked_at' => now(),
            'uses_count' => 1,
            'last_emailed_at' => null,
        ]);

        $payload = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $this->assertNull(collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_used_invitation_without_accepted_at_does_not_create_send_email_task(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
        ]);

        $sponsor = User::factory()->create();
        $sponsor->assignRole('agency-owner');

        $registeredMember = User::factory()->create(['email' => 'legacy.recruit@example.com']);

        RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'legacy.recruit@example.com',
            'accepted_by' => $registeredMember->id,
            'accepted_at' => null,
            'revoked_at' => null,
            'uses_count' => 1,
            'max_uses' => 1,
            'last_emailed_at' => null,
        ]);

        $payload = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $this->assertNull(collect($payload['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_registration_completes_send_invitation_email_task_for_sponsor(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $sponsor = User::factory()->create(['is_active' => true]);
        $sponsor->assignRole('member');

        $invitation = RegistrationInvitation::factory()->for($sponsor, 'sponsor')->create([
            'email' => 'new.recruit@example.com',
            'last_emailed_at' => null,
        ]);

        $countryId = LocationOptions::resolveCountryId('Canada');
        $stateProvinceId = LocationOptions::resolveStateProvinceId('Canada', 'British Columbia');
        $timezoneId = LocationOptions::resolveTimezoneId('Canada Pacific Time');

        $before = app(TaskController::class)->openTasksByPriorityFor($sponsor);
        $this->assertNotNull(collect($before['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));

        $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'New',
            'last_name' => 'Recruit',
            'email' => 'new.recruit@example.com',
            'gw_associate_id' => 'GW-9001',
            'city' => 'Vancouver',
            'country_id' => $countryId,
            'state_province_id' => $stateProvinceId,
            'timezone_id' => $timezoneId,
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login', absolute: false));

        $after = app(TaskController::class)->openTasksByPriorityFor($sponsor);

        $this->assertNull(collect($after['items'])->first(
            fn (array $item): bool => ($item['title'] ?? '') === 'Send invitation email'
        ));
    }

    public function test_new_task_button_opens_my_tasks_create_form_modal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('New Task', false)
            ->assertSee("Livewire.dispatch('open-my-tasks-modal', { createForm: true })", false)
            ->assertDontSee('Custom task creation will be available in a future release', false);
    }

    public function test_member_tasks_page_is_accessible_when_no_tasks_are_open(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('No tasks match your filters', false)
            ->assertSee('Quick search', false)
            ->assertSee('Previous', false)
            ->assertSee('Rows Per Page', false);
    }

    public function test_member_tasks_page_shows_assigned_task_user_records(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            \Database\Seeders\SystemTaskAssignorSeeder::class,
            \Database\Seeders\TaskCategorySeeder::class,
            \Database\Seeders\TaskSeeder::class,
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');

        \App\Models\TaskUser::query()->create(TaskUserAttributes::forSystemTask(
            NewMemberOnboardingTaskService::CATEGORY,
            NewMemberOnboardingTaskService::taskTitle(),
            [
                'assignee_id' => $member->id,
                'priority' => 'high',
                'status' => 'to_do',
                'related_module' => 'Onboarding',
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            NewMemberOnboardingTaskService::TASK_DESCRIPTION,
            'high',
        ));

        $this->actingAs($member)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Tasks\TasksPage::class)
            ->assertSee('Schedule onboarding start date', false)
            ->assertSee('Open My Onboarding', false);

        $payload = app(TaskController::class)->workspaceFor($member)['taskManagementPayload'];
        $this->assertSame(1, $payload['stats']['total']);
        $this->assertSame(1, $payload['stats']['assignedToMe']);
        $this->assertSame('Schedule onboarding start date', $payload['tasks'][0]['title']);
        $this->assertStringContainsString('/onboarding', (string) ($payload['tasks'][0]['actionUrl'] ?? ''));
        $this->assertSame('Open My Onboarding', $payload['tasks'][0]['actionLabel'] ?? null);
    }

    public function test_tasks_page_accepts_search_query_from_url(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $member = User::factory()->create(['name' => 'Taylor Task Search']);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('tasks.index', ['q' => 'Taylor']))
            ->assertOk()
            ->assertSee('Taylor Task Search', false);
    }
}
