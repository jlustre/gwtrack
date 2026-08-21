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
            ->assertSee('License & Compliance Lifecycle', false)
            ->assertSee('Life insurance licenses', false);
    }

    public function test_syncs_license_records_from_profile(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');
        $member->profile()->create([
            'is_licensed_life_insurance' => true,
            'insurance_licenses' => ['United States|California', 'Canada|Ontario'],
            'insurance_license_numbers' => [
                'United States|California' => 'CA-3003',
                'Canada|Ontario' => 'ON-1001',
            ],
            'insurance_license_expirations' => [
                'United States|California' => '2026-12-31',
                'Canada|Ontario' => '2027-06-30',
            ],
        ]);
        $member->load('profile');

        Livewire::actingAs($member)
            ->test(ComplianceLifecycleHub::class)
            ->assertSee('Life insurance licenses')
            ->assertSee('CA:USA')
            ->assertSee('CA-3003')
            ->assertSee('Dec 31, 2026')
            ->assertSee('ON:CAN')
            ->assertSee('ON-1001')
            ->assertSee('Jun 30, 2027')
            ->assertSee('License #')
            ->call('syncLicenses');

        $this->assertSame(2, MemberComplianceRecord::query()->where('user_id', $member->id)->count());

        $california = MemberComplianceRecord::query()
            ->where('user_id', $member->id)
            ->where('jurisdiction_key', 'United States|California')
            ->first();
        $ontario = MemberComplianceRecord::query()
            ->where('user_id', $member->id)
            ->where('jurisdiction_key', 'Canada|Ontario')
            ->first();

        $this->assertNotNull($california);
        $this->assertSame('CA-3003', $california->identifier);
        $this->assertSame('2026-12-31', $california->expiration_date?->toDateString());
        $this->assertNotNull($ontario);
        $this->assertSame('ON-1001', $ontario->identifier);
        $this->assertSame('2027-06-30', $ontario->expiration_date?->toDateString());
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
