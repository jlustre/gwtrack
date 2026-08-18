<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\RegistrationInvitation;
use App\Models\User;
use Database\Seeders\CrmModuleTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmModuleTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_module_test_seeder_creates_owner_prospects_recruits_and_invitations(): void
    {
        $this->seed(CrmModuleTestSeeder::class);

        $owner = User::query()->where('email', 'crm-test@gwtrack.com')->first();

        $this->assertNotNull($owner);
        $this->assertTrue($owner->hasRole('member'));

        $this->assertGreaterThanOrEqual(
            4,
            Prospect::query()
                ->where('owner_id', $owner->id)
                ->whereIn('funnel_type', ['insurance', 'both'])
                ->count(),
        );

        $this->assertGreaterThanOrEqual(
            4,
            Prospect::query()->where('owner_id', $owner->id)->where('funnel_type', 'recruiting')->count(),
        );

        $this->assertGreaterThanOrEqual(
            3,
            User::query()->where('sponsor_id', $owner->id)->count(),
        );

        $this->assertTrue(
            RegistrationInvitation::query()
                ->where('sponsor_id', $owner->id)
                ->whereNotNull('prospect_id')
                ->exists()
        );

        $this->actingAs($owner)
            ->get(route('team.prospects'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('team.recruiting.index'))
            ->assertOk()
            ->assertSee('Recruiting Pipeline', false);
    }
}
