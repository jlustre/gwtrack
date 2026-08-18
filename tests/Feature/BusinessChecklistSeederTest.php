<?php

namespace Tests\Feature;

use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessChecklistSeederTest extends TestCase
{
    use RefreshDatabase;

    private function typeId(string $code): int
    {
        return (int) DB::table('checklist_types')->where('code', $code)->value('id');
    }

    public function test_licensing_checklist_seeder_populates_relevant_steps(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $typeId = $this->typeId('licensing');

        $this->assertSame(13, DB::table('checklists')->where('checklist_type_id', $typeId)->count());
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Confirm Licensing Jurisdiction',
            'sort_order' => 10,
            'responsible_parties' => 'Self, SP, CFM',
            'notified_parties' => 'SP, CFM',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Receive License Approval',
            'responsible_parties' => 'Self, SP, AO, CFM',
            'notified_parties' => 'SP, AO, CFM',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Complete Carrier Or Product Appointment Steps',
            'is_required' => false,
        ]);
    }

    public function test_fap_checklist_seeder_populates_program_steps(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $typeId = $this->typeId('fap');

        $this->assertSame(14, DB::table('checklists')->where('checklist_type_id', $typeId)->count());
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'FAP Orientation With Sponsor And CFM',
            'sort_order' => 10,
            'responsible_parties' => 'Self, SP, CFM',
            'notified_parties' => 'SP, CFM',
        ]);
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Receive FAP Approval',
            'sort_order' => 140,
            'responsible_parties' => 'SP, AO, CFM',
            'notified_parties' => 'SP, CFM, AO',
        ]);
    }

    public function test_cfm_training_checklist_seeder_populates_relevant_modules(): void
    {
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(ChecklistSeeder::class);

        $typeId = $this->typeId('cfm-training');

        $this->assertSame(11, DB::table('checklists')->where('checklist_type_id', $typeId)->count());
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'CFM Role And Responsibility Orientation',
            'sort_order' => 10,
            'responsible_parties' => 'Self, SP, TR',
            'notified_parties' => 'SP, TR',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'CFM Certification Review',
            'responsible_parties' => 'SP, AO, TR',
            'notified_parties' => 'SP, AO, TR',
            'is_required' => true,
        ]);
        $this->assertDatabaseHas('checklists', [
            'checklist_type_id' => $typeId,
            'title' => 'Leadership Development Bonus Module',
            'is_required' => false,
        ]);
    }

    public function test_business_checklist_seeders_are_idempotent(): void
    {
        $this->seed([
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);
        $this->seed([
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $this->assertSame(13, DB::table('checklists')->where('checklist_type_id', $this->typeId('licensing'))->count());
        $this->assertSame(14, DB::table('checklists')->where('checklist_type_id', $this->typeId('fap'))->count());
        $this->assertSame(11, DB::table('checklists')->where('checklist_type_id', $this->typeId('cfm-training'))->count());
        $this->assertSame(1, DB::table('checklists')
            ->where('checklist_type_id', $this->typeId('licensing'))
            ->where('title', 'Pass Licensing Exam')
            ->count());
        $this->assertSame(1, DB::table('checklists')
            ->where('checklist_type_id', $this->typeId('cfm-training'))
            ->where('title', 'FAP Coaching Framework')
            ->count());
    }
}
