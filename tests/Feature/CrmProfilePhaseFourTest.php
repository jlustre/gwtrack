<?php

namespace Tests\Feature;

use App\Livewire\Prospects\ProspectTimeline;
use App\Models\CalendarEvent;
use App\Models\FnaRecord;
use App\Models\Prospect;
use App\Models\ProspectConversion;
use App\Models\ProspectFile;
use App\Models\ProspectFollowUp;
use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskUserAttributes;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CrmProfilePhaseFourTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Prospect $prospect;

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

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');

        $this->prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => DB::table('prospect_funnels')->where('key', 'insurance')->value('id'),
            'pipeline_stage_id' => DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id'),
            'funnel_type' => 'insurance',
            'first_name' => 'Phase',
            'last_name' => 'Four',
            'email' => 'phase.four@example.com',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);
    }

    public function test_profile_snapshot_shows_next_commitments_and_relationship_assets(): void
    {
        $this->seedRelationshipArtifacts();

        $this->actingAs($this->owner)
            ->get(route('team.prospects.records.show', $this->prospect))
            ->assertOk()
            ->assertSee('Next Meeting')
            ->assertSee('Strategy Review')
            ->assertSee('Next Follow-Up')
            ->assertSee('Open Tasks')
            ->assertSee('FNA Status')
            ->assertSee('Ready For Review')
            ->assertSee('Files');
    }

    public function test_unified_timeline_renders_phase_four_event_types_and_filters(): void
    {
        $this->seedRelationshipArtifacts();

        Livewire::actingAs($this->owner)
            ->test(ProspectTimeline::class, ['prospect' => $this->prospect])
            ->assertSee('Calendar event')
            ->assertSee('Strategy Review')
            ->assertSee('Renewal follow-up')
            ->assertSee('Review client documents')
            ->assertSee('FNA Ready for Review')
            ->assertSee('Client')
            ->assertSee('File uploaded')
            ->set('filter', 'fna')
            ->assertSee('FNA Ready for Review')
            ->assertDontSee('Strategy Review')
            ->set('filter', 'task')
            ->assertSee('Review client documents')
            ->assertDontSee('Client onboarding packet.pdf');
    }

    private function seedRelationshipArtifacts(): void
    {
        CalendarEvent::create([
            'calendar_event_type_id' => DB::table('calendar_event_types')->where('slug', 'sales-appointment')->value('id'),
            'calendar_category_id' => DB::table('calendar_categories')->where('slug', 'prospects')->value('id'),
            'organizer_id' => $this->owner->id,
            'title' => 'Strategy Review',
            'description' => 'Prepare for premium CRM review.',
            'starts_at' => now()->addDay()->setTime(13, 0),
            'ends_at' => now()->addDay()->setTime(14, 0),
            'timezone' => 'America/Vancouver',
            'visibility' => 'private',
            'status' => 'scheduled',
            'related_prospect_id' => $this->prospect->id,
        ]);

        ProspectFollowUp::create([
            'prospect_id' => $this->prospect->id,
            'assigned_user_id' => $this->owner->id,
            'due_at' => now()->addHours(6),
            'followup_type' => 'renewal_followup',
            'priority' => 'high',
            'status' => 'pending',
            'notes' => 'Renewal follow-up',
        ]);

        TaskUser::create(TaskUserAttributes::forTask('Prospect Follow-Up', 'Review client documents', [
            'assignee_id' => $this->owner->id,
            'assignor_id' => $this->owner->id,
            'priority' => 'high',
            'status' => 'to_do',
            'related_module' => 'prospects',
            'related_person' => $this->prospect->displayName(),
            'related_prospect_id' => $this->prospect->id,
            'due_date' => now()->addDay()->toDateString(),
            'additional_notes' => 'Review client documents',
        ]));

        FnaRecord::create([
            'owner_user_id' => $this->owner->id,
            'created_by_user_id' => $this->owner->id,
            'prospect_id' => $this->prospect->id,
            'status' => 'ready_for_review',
            'title' => 'Phase Four FNA',
            'reference_code' => 'FNA-P4-001',
            'client_name' => $this->prospect->displayName(),
            'completeness_score' => 80,
        ]);

        ProspectConversion::create([
            'prospect_id' => $this->prospect->id,
            'converted_by' => $this->owner->id,
            'conversion_type' => 'client',
            'converted_at' => now()->subHour(),
            'policy_reference' => 'POL-P4',
            'notes' => 'Client',
        ]);

        ProspectFile::create([
            'prospect_id' => $this->prospect->id,
            'uploaded_by' => $this->owner->id,
            'name' => 'Client onboarding packet.pdf',
            'path' => 'prospects/client-onboarding-packet.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);
    }
}
