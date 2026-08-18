<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactReferral;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\RecruitingOpportunity;
use App\Models\SalesOpportunity;
use App\Models\User;
use App\Services\Prospects\CrmRelationshipExpansionService;
use App\Services\Prospects\ProspectCalendarBridge;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmRelationshipPhaseSixTest extends TestCase
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
            CalendarModuleSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_prospect_can_sync_to_contact_and_dual_opportunities_idempotently(): void
    {
        $prospect = $this->prospect('both');

        $payload = app(CrmRelationshipExpansionService::class)->syncProspect($prospect);
        $secondPayload = app(CrmRelationshipExpansionService::class)->syncProspect($prospect->fresh());

        $this->assertInstanceOf(Contact::class, $payload['contact']);
        $this->assertInstanceOf(SalesOpportunity::class, $payload['sales_opportunity']);
        $this->assertInstanceOf(RecruitingOpportunity::class, $payload['recruiting_opportunity']);
        $this->assertSame($payload['contact']->id, $secondPayload['contact']->id);
        $this->assertSame($payload['sales_opportunity']->id, $secondPayload['sales_opportunity']->id);
        $this->assertSame($payload['recruiting_opportunity']->id, $secondPayload['recruiting_opportunity']->id);

        $prospect->refresh();

        $this->assertSame($payload['contact']->id, $prospect->contact_id);
        $this->assertSame($payload['sales_opportunity']->id, $prospect->sales_opportunity_id);
        $this->assertSame($payload['recruiting_opportunity']->id, $prospect->recruiting_opportunity_id);
        $this->assertDatabaseHas('contacts', [
            'id' => $prospect->contact_id,
            'source_prospect_id' => $prospect->id,
            'email' => 'phase.six@example.com',
        ]);
    }

    public function test_referrals_are_formal_records_between_contacts_and_prospects(): void
    {
        $service = app(CrmRelationshipExpansionService::class);
        $referrer = $this->prospect('insurance', 'Referral', 'Partner');
        $referred = $this->prospect('recruiting', 'Referred', 'Candidate');

        $service->syncProspect($referrer);
        $service->syncProspect($referred);

        $referral = $service->referralFromProspects(
            referred: $referred->fresh(),
            referrer: $referrer->fresh(),
            sourceLabel: 'Client referral',
            notes: 'Introduced after a policy review.',
        );

        $this->assertInstanceOf(ContactReferral::class, $referral);
        $this->assertSame($referrer->id, $referral->referrer_prospect_id);
        $this->assertSame($referred->id, $referral->referred_prospect_id);
        $this->assertSame('recruiting', $referral->referral_type);
        $this->assertSame('Client referral', $referral->source_label);
    }

    public function test_calendar_events_link_to_contact_and_opportunity_when_synced(): void
    {
        $prospect = $this->prospect('insurance');
        app(CrmRelationshipExpansionService::class)->syncProspect($prospect);

        $appointment = ProspectAppointment::create([
            'prospect_id' => $prospect->id,
            'owner_id' => $this->owner->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'timezone' => 'America/Vancouver',
            'purpose' => 'Phase 6 opportunity meeting',
            'status' => 'scheduled',
        ]);

        $event = app(ProspectCalendarBridge::class)->pushAppointment($appointment);

        $this->assertSame($prospect->id, $event->related_prospect_id);
        $this->assertSame($prospect->fresh()->contact_id, $event->related_contact_id);
        $this->assertSame($prospect->fresh()->sales_opportunity_id, $event->related_sales_opportunity_id);
        $this->assertNull($event->related_recruiting_opportunity_id);
    }

    private function prospect(string $funnelType, string $firstName = 'Phase', string $lastName = 'Six'): Prospect
    {
        $funnelKey = $funnelType === 'recruiting' ? 'recruiting' : 'insurance';

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_source_id' => DB::table('prospect_sources')->where('name', 'Referral')->value('id'),
            'prospect_funnel_id' => DB::table('prospect_funnels')->where('key', $funnelKey)->value('id'),
            'pipeline_stage_id' => DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id'),
            'funnel_type' => $funnelType,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName).'.'.strtolower($lastName).'@example.com',
            'phone' => '555-0100',
            'city' => 'Vancouver',
            'country' => 'Canada',
            'interest_level' => 'hot',
            'priority' => 'high',
            'referral_source_name' => 'Referral Partner',
            'status' => 'active',
        ]);
    }
}
