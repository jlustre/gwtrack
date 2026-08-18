<?php

namespace Tests\Feature;

use App\Models\Checklist;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChecklistSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_seeder_populates_business_relevant_onboarding_items(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $onboardingTypeId = DB::table('checklist_types')->where('code', 'onboarding')->value('id');

        $this->assertSame(19, DB::table('checklists')->where('checklist_type_id', $onboardingTypeId)->count());

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $onboardingTypeId,
            'title' => 'Complete Member Profile',
            'sort_order' => 10,
            'responsible_parties' => 'Self',
            'notified_parties' => 'SP',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $onboardingTypeId,
            'title' => 'Receive Certified Field Mentor Assignment',
            'responsible_parties' => 'SP, AO',
            'notified_parties' => 'SP, CFM, AO',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $onboardingTypeId,
            'title' => 'Review Rank Advancement Path',
            'is_required' => false,
        ]);

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $onboardingTypeId,
            'title' => 'Canada: Review Provincial Licensing Path',
            'country' => 'Canada',
            'responsible_parties' => 'Self, SP, CFM',
            'notified_parties' => 'SP, CFM',
            'is_required' => true,
        ]);
    }

    public function test_checklist_seeder_is_idempotent(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $onboardingTypeId = DB::table('checklist_types')->where('code', 'onboarding')->value('id');

        $this->assertSame(19, DB::table('checklists')->where('checklist_type_id', $onboardingTypeId)->count());
        $this->assertSame(1, DB::table('checklists')
            ->where('checklist_type_id', $onboardingTypeId)
            ->where('title', 'Start Licensing Tracker')
            ->count());
    }

    public function test_onboarding_checklists_can_be_filtered_by_member_country(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $canadaSteps = Checklist::query()
            ->forTypeCode('onboarding')
            ->applicableToCountry('Canada')
            ->pluck('title');

        $this->assertTrue($canadaSteps->contains('Complete Member Profile'));
        $this->assertTrue($canadaSteps->contains('Canada: Review Provincial Licensing Path'));
        $this->assertFalse($canadaSteps->contains('United States: Review State Licensing Path'));

        $usSteps = Checklist::query()
            ->forTypeCode('onboarding')
            ->applicableToCountry('United States')
            ->pluck('title');

        $this->assertTrue($usSteps->contains('United States: Review State Licensing Path'));
        $this->assertFalse($usSteps->contains('Canada: Review Provincial Licensing Path'));
    }
}
