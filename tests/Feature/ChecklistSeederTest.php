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

        $this->assertSame(11, DB::table('checklists')->where('checklist_type_id', $onboardingTypeId)->count());

        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $onboardingTypeId,
            'title' => 'Complete Member Profile',
            'sort_order' => 30,
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
            'title' => 'Review Resource Library',
            'is_required' => false,
        ]);
    }

    public function test_checklist_seeder_is_idempotent(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $onboardingTypeId = DB::table('checklist_types')->where('code', 'onboarding')->value('id');

        $this->assertSame(11, DB::table('checklists')->where('checklist_type_id', $onboardingTypeId)->count());
        $this->assertSame(1, DB::table('checklists')
            ->where('checklist_type_id', $onboardingTypeId)
            ->where('title', 'Complete Member Profile')
            ->count());

        $contractingTypeId = DB::table('checklist_types')->where('code', 'contracting')->value('id');

        $this->assertSame(14, DB::table('checklists')->where('checklist_type_id', $contractingTypeId)->count());
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $contractingTypeId,
            'title' => 'Select Target Carriers',
            'responsible_parties' => 'Self, SP, AO',
            'is_required' => true,
        ]);
    }

    public function test_onboarding_checklists_include_global_items_for_member_country(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $canadaSteps = Checklist::query()
            ->forTypeCode('onboarding')
            ->applicableToCountry('Canada')
            ->pluck('title');

        $this->assertTrue($canadaSteps->contains('Complete Member Profile'));
        $this->assertTrue($canadaSteps->contains('Email Verified'));
    }
}
