<?php

namespace Tests\Feature;

use App\Livewire\ComplianceLifecycleHub;
use App\Models\MemberComplianceRecord;
use App\Models\User;
use Database\Seeders\ComplianceLifecycleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ComplianceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RankSeeder::class);
        $this->seed(ComplianceLifecycleSeeder::class);
    }

    public function test_member_can_view_compliance_hub(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');

        $this->actingAs($member)
            ->get(route('compliance.index'))
            ->assertOk()
            ->assertSee('License & Compliance Lifecycle', false);
    }

    public function test_syncs_license_records_from_profile(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');
        $member->profile()->create([
            'insurance_licenses' => ['United States|California', 'Canada|Ontario'],
        ]);
        $member->load('profile');

        Livewire::actingAs($member)
            ->test(ComplianceLifecycleHub::class)
            ->call('syncLicenses');

        $this->assertSame(2, MemberComplianceRecord::query()->where('user_id', $member->id)->count());
        $this->assertDatabaseHas('member_compliance_records', [
            'user_id' => $member->id,
            'compliance_type' => 'state_license',
            'jurisdiction_key' => 'United States|California',
        ]);
    }

    public function test_member_can_add_eo_insurance_record(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');

        Livewire::actingAs($member)
            ->test(ComplianceLifecycleHub::class)
            ->call('openCreateForm')
            ->set('complianceType', 'eo_insurance')
            ->set('title', '2026 E&O Policy')
            ->set('carrierName', 'Sample Carrier')
            ->set('expirationDate', now()->addMonths(6)->format('Y-m-d'))
            ->call('saveRecord');

        $this->assertDatabaseHas('member_compliance_records', [
            'user_id' => $member->id,
            'compliance_type' => 'eo_insurance',
            'title' => '2026 E&O Policy',
        ]);
    }

    public function test_expiration_updates_status_to_pending_renewal(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');

        $record = MemberComplianceRecord::query()->create([
            'user_id' => $member->id,
            'compliance_type' => 'aml_training',
            'title' => 'AML Refresher',
            'status' => 'active',
            'effective_date' => now()->subYear()->toDateString(),
            'expiration_date' => now()->addDays(14)->toDateString(),
            'renewal_window_days' => 30,
            'verified_at' => now(),
        ]);

        app(\App\Services\ComplianceLifecycleService::class)->refreshStatus($record);

        $this->assertSame('pending_renewal', $record->fresh()->status);
    }

    public function test_team_leader_can_verify_member_compliance_record(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $member = User::factory()->create();
        $member->assignRole('associate');
        $member->update(['sponsor_id' => $leader->id]);

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $record = MemberComplianceRecord::query()->create([
            'user_id' => $member->id,
            'compliance_type' => 'eo_insurance',
            'title' => 'E&O Policy',
            'status' => 'pending_verification',
        ]);

        Livewire::actingAs($leader)
            ->test(ComplianceLifecycleHub::class, ['member' => $member->id])
            ->call('verifyRecord', $record->id);

        $this->assertNotNull($record->fresh()->verified_at);
    }
}
