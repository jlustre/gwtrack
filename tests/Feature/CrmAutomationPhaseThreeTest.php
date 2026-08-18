<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\ProspectFollowUp;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\Prospects\CrmAutomationService;
use App\Services\Prospects\ProspectFunnelService;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmAutomationPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $this->owner = User::factory()->create([
            'name' => 'CRM Automation Owner',
            'email' => 'crm-automation-owner@example.com',
        ]);
        $this->owner->assignRole('member');
    }

    public function test_activity_next_action_creates_followup_task_and_calendar_event(): void
    {
        $prospect = $this->prospect();
        $dueAt = now()->addDays(2)->setTime(10, 30);

        app(ProspectFunnelService::class)->logActivity($prospect, $this->owner, [
            'activity_type' => 'phone_call',
            'occurred_at' => now(),
            'outcome' => 'Connected',
            'notes' => 'Discussed coverage options.',
            'next_action' => 'Send the quote and confirm review time.',
            'next_follow_up_at' => $dueAt,
        ]);

        $followUp = ProspectFollowUp::query()->where('prospect_id', $prospect->id)->first();

        $this->assertNotNull($followUp);
        $this->assertSame('activity_next_action', $followUp->followup_type);
        $this->assertSame('Send the quote and confirm review time.', $followUp->notes);

        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $this->owner->id,
            'related_prospect_id' => $prospect->id,
            'priority' => 'medium',
        ]);

        $event = CalendarEvent::query()->where('related_prospect_id', $prospect->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('crm-follow-up', $event->type?->slug);
        $this->assertSame($dueAt->toDateTimeString(), $event->starts_at->toDateTimeString());
        $this->assertDatabaseHas('calendar_event_attendees', [
            'calendar_event_id' => $event->id,
            'prospect_id' => $prospect->id,
            'attendee_type' => 'prospect',
        ]);
    }

    public function test_communication_next_action_uses_same_automation_path(): void
    {
        $prospect = $this->prospect('recruiting');

        app(ProspectFunnelService::class)->logCommunication($prospect, $this->owner, [
            'direction' => 'outbound',
            'contacted_at' => now(),
            'outcome' => 'Left voicemail',
            'next_action' => 'Send recruiting overview link.',
            'next_follow_up_at' => now()->addDay()->setTime(16, 0),
        ]);

        $this->assertDatabaseHas('prospect_followups', [
            'prospect_id' => $prospect->id,
            'followup_type' => 'communication_next_action',
            'notes' => 'Send recruiting overview link.',
        ]);
        $this->assertDatabaseHas('task_users', [
            'related_prospect_id' => $prospect->id,
            'assignee_id' => $this->owner->id,
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'related_prospect_id' => $prospect->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_stage_entered_automation_creates_followup_and_task_once(): void
    {
        $prospect = $this->prospect();
        $presentationStageId = PipelineStage::query()->where('slug', 'presentation-completed')->value('id');

        app(ProspectFunnelService::class)->moveStage($prospect, $this->owner, (int) $presentationStageId, 'kanban');
        app(ProspectFunnelService::class)->moveStage($prospect->fresh(), $this->owner, (int) $presentationStageId, 'kanban');

        $this->assertSame(1, ProspectFollowUp::query()
            ->where('prospect_id', $prospect->id)
            ->where('followup_type', 'post_presentation')
            ->count());

        $this->assertSame(1, TaskUser::query()
            ->where('related_prospect_id', $prospect->id)
            ->whereHas('task', fn ($query) => $query->where('title', 'Schedule post-presentation follow-up'))
            ->count());
    }

    public function test_missed_appointment_creates_reschedule_followup_and_task(): void
    {
        $prospect = $this->prospect();
        $appointment = ProspectAppointment::create([
            'prospect_id' => $prospect->id,
            'owner_id' => $this->owner->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'no_show',
            'purpose' => 'Discovery call',
        ]);

        app(CrmAutomationService::class)->handleMissedAppointment($appointment);

        $this->assertDatabaseHas('prospect_followups', [
            'prospect_id' => $prospect->id,
            'followup_type' => 'missed_appointment',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('task_users', [
            'related_prospect_id' => $prospect->id,
            'priority' => 'high',
        ]);
    }

    private function prospect(string $funnelType = 'insurance'): Prospect
    {
        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => DB::table('prospect_funnels')->where('key', $funnelType)->value('id'),
            'pipeline_stage_id' => DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id'),
            'funnel_type' => $funnelType,
            'first_name' => 'Automation',
            'last_name' => str($funnelType)->headline()->toString(),
            'email' => 'automation-'.$funnelType.'@example.com',
            'phone' => '555-1212',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);
    }
}
