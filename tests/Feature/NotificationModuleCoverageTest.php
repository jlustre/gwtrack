<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Notification;
use App\Models\User;
use App\Services\CfmAssignmentWorkflowService;
use App\Services\CfmPortal\CfmTaskService;
use App\Services\ChecklistService;
use App\Services\Messaging\MessagingService;
use App\Services\NewMemberRegistrationService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class NotificationModuleCoverageTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
        ]);
    }

    public function test_registration_dispatches_cfm_reminder_notifications(): void
    {
        $agencyOwner = User::factory()->create(['name' => 'Agency Owner']);
        $agencyOwner->assignRole('agency-owner');

        $sponsor = User::factory()->create([
            'name' => 'Direct Sponsor',
            'sponsor_id' => $agencyOwner->id,
        ]);
        $sponsor->assignRole('member');

        $member = User::factory()->create([
            'name' => 'New Recruit',
            'sponsor_id' => $sponsor->id,
        ]);
        $member->assignRole('member');

        app(NewMemberRegistrationService::class)->process($member);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $agencyOwner->id)
                ->where('data->trigger', 'assign_cfm_reminder')
                ->exists()
        );

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $sponsor->id)
                ->where('data->trigger', 'recommend_cfm_reminder')
                ->exists()
        );
    }

    public function test_checklist_submission_notifies_reviewer(): void
    {
        $this->seed([ChecklistTypeSeeder::class, ChecklistSeeder::class]);

        $sponsor = User::factory()->create(['name' => 'Sponsor Reviewer']);
        $sponsor->assignRole('member');

        $member = User::factory()->create([
            'name' => 'Checklist Member',
            'sponsor_id' => $sponsor->id,
        ]);
        $member->assignRole('member');

        $this->startChecklistType($member, 'onboarding');

        $checklistId = (int) \App\Models\Checklist::query()
            ->whereHas('type', fn ($q) => $q->where('code', 'onboarding'))
            ->where('title', 'Complete Member Profile')
            ->value('id');

        app(ChecklistService::class)->updateUserProgress($member, $checklistId, true);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $sponsor->id)
                ->where('data->trigger', 'checklist_item_submitted')
                ->exists()
        );
    }

    public function test_cfm_assignment_activation_dispatches_mentor_assigned_notification(): void
    {
        $this->seed([TaskScenarioSeeder::class, CfmManagementSeeder::class, EmailTemplateSeeder::class]);

        $assignment = \App\Models\MentorAssignment::query()
            ->where('status', 'pending')
            ->firstOrFail();

        app(CfmAssignmentWorkflowService::class)->activateAssignment($assignment);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $assignment->apprentice_id)
                ->where('data->trigger', 'mentor_assigned')
                ->exists()
        );
    }

    public function test_messaging_service_dispatches_message_received_notification(): void
    {
        $sender = User::factory()->create(['name' => 'Sender User']);
        $sender->assignRole('member');
        $sender->givePermissionTo(['view conversations', 'send messages']);

        $recipient = User::factory()->create(['name' => 'Recipient User']);
        $recipient->assignRole('member');
        $recipient->givePermissionTo(['view conversations', 'send messages']);

        $conversation = Conversation::query()->create([
            'type' => 'direct',
            'created_by' => $sender->id,
            'last_message_at' => now(),
        ]);

        ConversationMember::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'member_role' => 'member',
            'joined_at' => now(),
        ]);

        ConversationMember::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $recipient->id,
            'member_role' => 'member',
            'joined_at' => now(),
        ]);

        app(MessagingService::class)->sendMessage($sender, $conversation, 'Hello from Phase 3 coverage test.');

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $recipient->id)
                ->where('data->trigger', 'message_received')
                ->exists()
        );
    }

    public function test_cfm_task_creation_dispatches_task_assigned_notification(): void
    {
        $this->seed([TaskScenarioSeeder::class, CfmManagementSeeder::class]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $assignment = \App\Models\MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();

        $trainee = $assignment->apprentice;

        app(CfmTaskService::class)->create($cfm, $trainee, $cfm, [
            'title' => 'Complete weekly activity log',
            'priority' => 'high',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $trainee->id)
                ->where('data->trigger', 'task_assigned')
                ->exists()
        );
    }
}
