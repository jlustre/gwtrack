<?php

namespace Tests\Feature;

use App\Livewire\Reports\LeadershipReports;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LeadershipReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_leadership_report_is_scoped_to_authorized_hierarchy_and_supports_status_filtering(): void
    {
        $leader = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
        $leader->assignRole('team-leader');
        $downline = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => false,
            'sponsor_id' => $leader->id,
        ]);
        $downline->assignRole('member');
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $outsider->assignRole('member');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $downline->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $downline->id, 'descendant_id' => $downline->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $outsider->id, 'descendant_id' => $outsider->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($leader)
            ->get(route('reports.leadership'))
            ->assertOk()
            ->assertSeeText($leader->name)
            ->assertSeeText($downline->name)
            ->assertDontSeeText($outsider->name);

        Livewire::actingAs($leader)
            ->test(LeadershipReports::class)
            ->set('status', 'inactive')
            ->assertSeeText($downline->name)
            ->assertDontSeeText($leader->name)
            ->assertDontSeeText($outsider->name);
    }

    public function test_member_cannot_open_leadership_report(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('reports.leadership'))
            ->assertForbidden();
    }

    public function test_leader_can_export_only_visible_member_rows(): void
    {
        $leader = User::factory()->create(['email_verified_at' => now()]);
        $leader->assignRole('team-leader');
        $member = User::factory()->create(['email_verified_at' => now(), 'sponsor_id' => $leader->id]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $member->id, 'descendant_id' => $member->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $outsider->id, 'descendant_id' => $outsider->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($leader)->get(route('reports.leadership.download', ['period' => 'monthly']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString($member->name, $content);
        $this->assertStringNotContainsString($outsider->name, $content);
    }
}
