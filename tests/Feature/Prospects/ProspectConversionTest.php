<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use App\Models\ProspectConversion;
use App\Models\RegistrationInvitation;
use App\Models\Team;
use App\Models\User;
use App\Services\Prospects\ProspectConversionService;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectConversionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            RankSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_associate_conversion_creates_invitation_and_conversion_record(): void
    {
        $prospect = $this->makeRecruitingProspect([
            'email' => 'recruit@example.com',
        ]);

        $result = app(ProspectConversionService::class)->convertToAssociate(
            $prospect,
            $this->owner,
            'Ready to register',
        );

        $this->assertInstanceOf(RegistrationInvitation::class, $result['invitation']);
        $this->assertSame($prospect->id, $result['invitation']->prospect_id);
        $this->assertSame('recruit@example.com', $result['invitation']->email);
        $this->assertStringContainsString($result['invitation']->code, $result['invitation_url']);

        $this->assertDatabaseHas('prospect_conversions', [
            'prospect_id' => $prospect->id,
            'conversion_type' => 'associate',
            'converted_by' => $this->owner->id,
            'notes' => 'Ready to register',
            'created_user_id' => null,
        ]);

        $registrationStageId = DB::table('pipeline_stages')->where('slug', 'registration-link-sent')->value('id');
        $this->assertSame($registrationStageId, $prospect->fresh()->pipeline_stage_id);

        $this->assertDatabaseHas('prospect_access_logs', [
            'prospect_id' => $prospect->id,
            'actor_id' => $this->owner->id,
            'action' => 'conversion_associate_initiated',
        ]);
    }

    public function test_registration_completes_associate_conversion_and_links_user(): void
    {
        $team = Team::create([
            'owner_id' => $this->owner->id,
            'leader_id' => $this->owner->id,
            'name' => 'Conversion Team',
            'is_active' => true,
        ]);
        $this->owner->forceFill(['team_id' => $team->id])->save();

        $prospect = $this->makeRecruitingProspect([
            'email' => 'new.recruit@example.com',
        ]);

        $result = app(ProspectConversionService::class)->convertToAssociate($prospect, $this->owner);
        $invitation = $result['invitation'];

        $response = $this->post('/register', [
            'registration_code' => $invitation->code,
            'first_name' => 'New',
            'last_name' => 'Recruit',
            'email' => 'new.recruit@example.com',
            'gw_associate_id' => 'GW-9001',
            'city' => 'Vancouver',
            'country_id' => LocationOptions::resolveCountryId('Canada'),
            'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'British Columbia'),
            'timezone_id' => LocationOptions::resolveTimezoneId('Canada Pacific Time'),
            'sponsor_confirmed' => '1',
            'active_associate_confirmed' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login', absolute: false));

        $newMember = User::where('email', 'new.recruit@example.com')->firstOrFail();

        $this->assertDatabaseHas('prospect_conversions', [
            'prospect_id' => $prospect->id,
            'conversion_type' => 'associate',
            'created_user_id' => $newMember->id,
        ]);

        $prospect->refresh();
        $this->assertSame('associate', $prospect->converted_to);
        $this->assertNotNull($prospect->conversion_at);

        $associateStageId = DB::table('pipeline_stages')->where('slug', 'became-associate')->value('id');
        $this->assertSame($associateStageId, $prospect->pipeline_stage_id);
    }

    public function test_client_conversion_sets_policy_reference_and_marks_client(): void
    {
        $prospect = $this->makeInsuranceProspect();

        $conversion = app(ProspectConversionService::class)->convertToClient(
            $prospect,
            $this->owner,
            'POL-12345',
            'APP-67890',
            'Issued whole life policy',
        );

        $this->assertInstanceOf(ProspectConversion::class, $conversion);
        $this->assertSame('POL-12345', $conversion->policy_reference);
        $this->assertSame('APP-67890', $conversion->application_reference);

        $prospect->refresh();
        $this->assertTrue($prospect->is_client);
        $this->assertSame('client', $prospect->converted_to);
        $this->assertNotNull($prospect->conversion_at);

        $clientStageId = DB::table('pipeline_stages')->where('slug', 'became-client')->value('id');
        $this->assertSame($clientStageId, $prospect->pipeline_stage_id);
    }

    public function test_prospect_record_shows_conversion_history(): void
    {
        $prospect = $this->makeInsuranceProspect();

        app(ProspectConversionService::class)->convertToClient(
            $prospect,
            $this->owner,
            'POL-999',
        );

        $this->actingAs($this->owner)
            ->get(route('team.prospects.records.show', $prospect))
            ->assertOk()
            ->assertSee('Conversion History')
            ->assertSee('Client')
            ->assertSee('POL-999');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRecruitingProspect(array $overrides = []): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'presentation-completed')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'funnel_type' => 'recruiting',
            'first_name' => 'Recruit',
            'last_name' => 'Candidate',
            'interest_level' => 'hot',
            'priority' => 'high',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeInsuranceProspect(array $overrides = []): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'application-submitted')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'funnel_type' => 'insurance',
            'first_name' => 'Insurance',
            'last_name' => 'Lead',
            'interest_level' => 'warm',
            'priority' => 'medium',
            ...$overrides,
        ]);
    }
}
