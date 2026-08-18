<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectFollowUpList;
use App\Models\Prospect;
use App\Models\ProspectFollowUp;
use App\Models\User;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectFollowUpListTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_follow_up_center_page_renders_livewire_list(): void
    {
        $this->actingAs($this->owner)
            ->get(route('team.prospects.follow-ups'))
            ->assertOk()
            ->assertSee('Follow-Up Center')
            ->assertSeeLivewire(ProspectFollowUpList::class);
    }

    public function test_follow_up_list_supports_filters_and_actions(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Follow',
            'last_name' => 'UpTarget',
            'interest_level' => 'hot',
            'priority' => 'high',
        ]);

        $followUp = ProspectFollowUp::create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $this->owner->id,
            'due_at' => now()->addDay(),
            'followup_type' => 'manual_check',
            'priority' => 'high',
            'status' => 'pending',
            'notes' => 'Call to confirm next steps.',
        ]);

        $originalDue = $followUp->due_at->copy();

        Livewire::actingAs($this->owner)
            ->test(ProspectFollowUpList::class)
            ->assertSee('Follow UpTarget')
            ->assertSee('Manual Check')
            ->set('priorityFilter', 'high')
            ->assertSee('Follow UpTarget')
            ->call('snooze', $followUp->id)
            ->assertHasNoErrors();

        $followUp->refresh();
        $this->assertTrue($followUp->due_at->equalTo($originalDue->addDay()));

        Livewire::actingAs($this->owner)
            ->test(ProspectFollowUpList::class)
            ->call('markComplete', $followUp->id);

        $this->assertSame('completed', $followUp->fresh()->status);
        $this->assertNotNull($followUp->fresh()->completed_at);
    }

    public function test_user_cannot_update_another_users_follow_up(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $prospect = Prospect::create([
            'owner_id' => $otherUser->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Private',
            'last_name' => 'FollowUp',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $followUp = ProspectFollowUp::create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $otherUser->id,
            'due_at' => now(),
            'followup_type' => 'manual',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->owner)
            ->test(ProspectFollowUpList::class)
            ->call('markComplete', $followUp->id)
            ->assertForbidden();

        $this->assertSame('pending', $followUp->fresh()->status);
    }
}
