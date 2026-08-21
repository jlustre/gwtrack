<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\User;
use App\Services\MonetizationSettingsService;
use App\Services\SubscriptionService;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            RankSeeder::class,
        ]);
    }

    public function test_unlicensed_associates_are_free_even_at_paid_ranks(): void
    {
        $this->enableMonetization();

        $user = $this->userAtRank('SA');

        $this->assertSame(0.0, $this->subscriptions()->currentCharge($user));
    }

    public function test_licensed_training_associate_stays_free(): void
    {
        $this->enableMonetization();

        $user = $this->licensedUserAtRank('TA');

        $this->assertSame(0.0, $this->subscriptions()->currentCharge($user));
    }

    public function test_licensed_senior_associate_is_charged_sa_price(): void
    {
        $this->enableMonetization();

        $user = $this->licensedUserAtRank('SA');

        $this->assertSame(9.95, $this->subscriptions()->currentCharge($user));

        $invoice = $this->subscriptions()->invoicesFor($user)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(9.95, (float) $invoice->amount);
        $this->assertSame('SA', $invoice->rank_code);
        $this->assertSame('issued', $invoice->status);
        $this->assertStringStartsWith('GW-', $invoice->number);
        $this->assertSame('monthly', $invoice->billing_interval);
    }

    public function test_annual_plan_rewrites_unpaid_invoice_to_twelve_months(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');
        $this->enableMonetization();

        $user = $this->licensedUserAtRank('SA');
        $this->assertSame(9.95, $this->subscriptions()->currentCharge($user));

        $this->subscriptions()->updatePlan($user->fresh(['rank', 'profile', 'subscription']), 'annual');
        $user = $user->fresh(['rank', 'profile', 'subscription']);

        $this->assertSame('annual', $user->subscription->billing_interval);
        $this->assertSame(119.40, $this->subscriptions()->currentCharge($user));
        $this->assertSame('2027-03-10', $user->subscription->next_billing_at?->toDateString());

        $invoice = $this->subscriptions()->invoicesFor($user)->first();
        $this->assertSame(119.40, (float) $invoice->amount);
        $this->assertSame('annual', $invoice->billing_interval);
        $this->assertSame('2027-03-09', $invoice->period_end->toDateString());
    }

    public function test_monthly_auto_pay_marks_the_next_invoice_paid(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');
        $this->enableMonetization();

        $user = $this->licensedUserAtRank('SA');
        $this->subscriptions()->updatePlan($user->fresh(['rank', 'profile', 'subscription']), 'monthly_auto');

        $user->subscription->forceFill([
            'auto_pay' => true,
            'auto_pay_brand' => 'Visa',
            'auto_pay_last_four' => '4242',
        ])->save();

        Carbon::setTestNow('2026-04-10 09:00:00');
        $user->unsetRelation('subscription');
        $this->subscriptions()->currentCharge($user->fresh(['rank', 'profile', 'subscription']));

        $invoices = $this->subscriptions()->invoicesFor($user->fresh());
        $this->assertCount(2, $invoices);
        $latest = $invoices->first();
        $this->assertSame('paid', $latest->status);
        $this->assertSame(9.95, (float) $latest->amount);
        $this->assertSame('Visa •••• 4242', $latest->payment_reference);
        $this->assertSame('Automatic Monthly payment.', $latest->payment_notes);
    }

    public function test_rank_increase_waits_until_next_anniversary(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');
        $this->enableMonetization();

        $user = $this->licensedUserAtRank('SA');
        $this->assertSame(9.95, $this->subscriptions()->currentCharge($user));

        $user->update(['rank_id' => Rank::query()->where('code', 'MB')->value('id')]);
        $user->refresh()->load(['rank', 'subscription', 'profile']);

        $this->assertSame(9.95, $this->subscriptions()->currentCharge($user));
        $this->assertSame('SA', $user->subscription->billed_rank_code);
        $this->assertSame('MB', $user->subscription->pending_rank_code);

        Carbon::setTestNow('2026-04-10 09:00:00');
        $user->unsetRelation('subscription');

        $this->assertSame(14.95, $this->subscriptions()->currentCharge($user->fresh(['rank', 'profile', 'subscription'])));
        $this->assertSame('MB', $user->fresh()->subscription->billed_rank_code);
        $this->assertNull($user->fresh()->subscription->pending_rank_code);

        $invoices = $this->subscriptions()->invoicesFor($user->fresh());
        $this->assertCount(2, $invoices);
        $this->assertSame(9.95, (float) $invoices->last()->amount);
        $this->assertSame('SA', $invoices->last()->rank_code);
        $this->assertSame(14.95, (float) $invoices->first()->amount);
        $this->assertSame('MB', $invoices->first()->rank_code);
    }

    public function test_avp_and_svp_share_the_same_price(): void
    {
        $this->enableMonetization();

        $avp = $this->licensedUserAtRank('AVP');
        $svp = $this->licensedUserAtRank('SVP');

        $this->assertSame(24.95, $this->subscriptions()->currentCharge($avp));
        $this->assertSame(24.95, $this->subscriptions()->currentCharge($svp));
    }

    public function test_charges_are_zero_when_monetization_is_off(): void
    {
        $user = $this->licensedUserAtRank('NEVC');

        $this->assertSame(0.0, $this->subscriptions()->currentCharge($user));
    }

    private function enableMonetization(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);
    }

    private function subscriptions(): SubscriptionService
    {
        return app(SubscriptionService::class);
    }

    private function userAtRank(string $code): User
    {
        $user = User::factory()->create([
            'rank_id' => Rank::query()->where('code', $code)->value('id'),
        ]);
        $user->assignRole('member');
        $user->profile()->create([]);

        return $user->fresh(['rank', 'profile']);
    }

    private function licensedUserAtRank(string $code): User
    {
        $user = $this->userAtRank($code);
        $user->profile->update([
            'license_number' => 'LIC-'.$user->id,
            'is_licensed_life_insurance' => true,
        ]);

        return $user->fresh(['rank', 'profile', 'subscription']);
    }
}
