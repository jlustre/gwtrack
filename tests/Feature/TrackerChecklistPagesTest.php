<?php

namespace Tests\Feature;

use App\Livewire\Trackers\TrackerChecklistPage;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class TrackerChecklistPagesTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    public function test_tracker_pages_show_checklists_and_progress_stats(): void
    {
        $this->seedTrackerData();

        $user = $this->member();
        $this->startMemberChecklistTypes($user);

        $this->actingAs($user)
            ->get(route('licensing.index'))
            ->assertOk()
            ->assertSee('Licensing Tracker')
            ->assertSee('Overall Progress')
            ->assertSee('Confirm Licensing Jurisdiction')
            ->assertSee('Responsible:')
            ->assertSee('Notify:');

        $this->actingAs($user)
            ->get(route('apprenticeship.index'))
            ->assertOk()
            ->assertSee('Field Apprenticeship')
            ->assertSee('FAP Orientation With Sponsor And CFM');

        $this->actingAs($user)
            ->get(route('cfm-training.index'))
            ->assertOk()
            ->assertSee('CFM Training')
            ->assertSee('CFM Role And Responsibility Orientation');
    }

    public function test_user_can_submit_and_reopen_tracker_items_for_confirmation(): void
    {
        $this->seedTrackerData();

        $user = $this->member();
        $this->startChecklistType($user, 'licensing');
        $stepId = $this->checklistId('licensing', 'Confirm Licensing Jurisdiction');

        $this->actingAs($user)
            ->patch(route('licensing.update', $stepId), ['completed' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $user->id,
            'checklist_id' => $stepId,
            'mentor_assignment_id' => null,
            'status' => 'pending_confirmation',
            'completed_at' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('licensing.update', $stepId), ['completed' => '0'])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $user->id,
            'checklist_id' => $stepId,
            'mentor_assignment_id' => null,
            'status' => 'not_started',
            'completed_at' => null,
        ]);
    }

    public function test_livewire_tracker_toggles_items_without_full_page_reload(): void
    {
        $this->seedTrackerData();

        $user = $this->member();
        $this->startChecklistType($user, 'licensing');
        $stepId = $this->checklistId('licensing', 'Confirm Licensing Jurisdiction');

        Livewire::actingAs($user)
            ->test(TrackerChecklistPage::class, ['trackerKey' => 'licensing'])
            ->assertSet('trackerKey', 'licensing')
            ->assertSee('Licensing Tracker', false)
            ->call('toggleStep', $stepId, true)
            ->assertSet('statusMessage', 'licensing-item-submitted-for-confirmation')
            ->assertSee('Pending Confirmation', false);

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $user->id,
            'checklist_id' => $stepId,
            'status' => 'pending_confirmation',
        ]);

        Livewire::actingAs($user)
            ->test(TrackerChecklistPage::class, ['trackerKey' => 'licensing'])
            ->call('toggleStep', $stepId, false)
            ->assertSet('statusMessage', 'licensing-item-reopened');

        $this->assertDatabaseHas('checklist_progress', [
            'user_id' => $user->id,
            'checklist_id' => $stepId,
            'status' => 'not_started',
        ]);
    }

    public function test_user_cannot_uncheck_confirmed_tracker_items(): void
    {
        $this->seedTrackerData();

        $sponsor = $this->member();
        $user = $this->member(['sponsor_id' => $sponsor->id]);
        $this->startChecklistType($user, 'licensing', $sponsor);
        $stepId = $this->checklistId('licensing', 'Confirm Licensing Jurisdiction');

        $this->actingAs($user)
            ->patch(route('licensing.update', $stepId), ['completed' => '1'])
            ->assertRedirect();

        $progressId = DB::table('checklist_progress')
            ->where('user_id', $user->id)
            ->where('checklist_id', $stepId)
            ->whereNull('mentor_assignment_id')
            ->value('id');

        $this->actingAs($sponsor)
            ->patch(route('licensing.review', $progressId), [
                'decision' => 'confirmed',
                'review_comments' => 'Looks good.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checklist_progress', [
            'id' => $progressId,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->patch(route('licensing.update', $stepId), ['completed' => '0'])
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(TrackerChecklistPage::class, ['trackerKey' => 'licensing'])
            ->call('toggleStep', $stepId, false)
            ->assertForbidden();

        $this->assertDatabaseHas('checklist_progress', [
            'id' => $progressId,
            'status' => 'completed',
        ]);
    }

    public function test_sponsor_can_confirm_or_reject_pending_tracker_items_with_comments(): void
    {
        $this->seedTrackerData();

        $sponsor = $this->member();
        $user = $this->member(['sponsor_id' => $sponsor->id]);
        $this->startMemberChecklistTypes($user, $sponsor);

        $trackers = [
            [
                'type_code' => 'licensing',
                'step_title' => 'Confirm Licensing Jurisdiction',
                'update_route' => 'licensing.update',
                'review_route' => 'licensing.review',
            ],
            [
                'type_code' => 'fap',
                'step_title' => 'FAP Orientation With Sponsor And CFM',
                'update_route' => 'apprenticeship.update',
                'review_route' => 'apprenticeship.review',
            ],
            [
                'type_code' => 'cfm-training',
                'step_title' => 'CFM Role And Responsibility Orientation',
                'update_route' => 'cfm-training.update',
                'review_route' => 'cfm-training.review',
            ],
        ];

        foreach ($trackers as $tracker) {
            $stepId = $this->checklistId($tracker['type_code'], $tracker['step_title']);

            $this->actingAs($user)
                ->patch(route($tracker['update_route'], $stepId), ['completed' => '1'])
                ->assertRedirect();

            $progressId = DB::table('checklist_progress')
                ->where('user_id', $user->id)
                ->where('checklist_id', $stepId)
                ->whereNull('mentor_assignment_id')
                ->value('id');

            $this->actingAs($sponsor)
                ->patch(route($tracker['review_route'], $progressId), [
                    'decision' => 'rejected',
                    'review_comments' => 'Please add proof before approval.',
                ])
                ->assertRedirect();

            $this->assertDatabaseHas('checklist_progress', [
                'id' => $progressId,
                'status' => 'rejected',
                'reviewed_by' => $sponsor->id,
                'review_comments' => 'Please add proof before approval.',
                'completed_at' => null,
            ]);

            $this->actingAs($user)
                ->patch(route($tracker['update_route'], $stepId), ['completed' => '1'])
                ->assertRedirect();

            $this->actingAs($sponsor)
                ->patch(route($tracker['review_route'], $progressId), [
                    'decision' => 'confirmed',
                    'review_comments' => 'Confirmed.',
                ])
                ->assertRedirect();

            $this->assertDatabaseHas('checklist_progress', [
                'id' => $progressId,
                'status' => 'completed',
                'reviewed_by' => $sponsor->id,
                'review_comments' => 'Confirmed.',
            ]);

            $this->assertNotNull(DB::table('checklist_progress')->where('id', $progressId)->value('completed_at'));
        }
    }

    public function test_unnotified_user_cannot_review_pending_tracker_item(): void
    {
        $this->seedTrackerData();

        $user = $this->member();
        $otherUser = $this->member();
        $this->startChecklistType($user, 'licensing');

        $stepId = $this->checklistId('licensing', 'Confirm Licensing Jurisdiction');

        $this->actingAs($user)
            ->patch(route('licensing.update', $stepId), ['completed' => '1'])
            ->assertRedirect();

        $progressId = DB::table('checklist_progress')
            ->where('user_id', $user->id)
            ->where('checklist_id', $stepId)
            ->whereNull('mentor_assignment_id')
            ->value('id');

        $this->actingAs($otherUser)
            ->patch(route('licensing.review', $progressId), [
                'decision' => 'confirmed',
                'review_comments' => 'Trying to approve.',
            ])
            ->assertForbidden();
    }

    private function seedTrackerData(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);
    }

    private function member(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('member');
        $user->profile()->create(array_merge([
            'is_gw_active_associate' => true,
            'gw_associate_id' => 'GW-TRACK-'.$user->id,
        ], LocationOptions::profileLocationIds('Canada')));

        return $user;
    }

    private function checklistId(string $typeCode, string $title): int
    {
        return (int) DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', $typeCode)
            ->where('checklists.title', $title)
            ->value('checklists.id');
    }
}

