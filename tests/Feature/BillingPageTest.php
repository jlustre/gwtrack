<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Services\MonetizationSettingsService;
use App\Services\SubscriptionService;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
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

    public function test_my_billing_is_hidden_when_monetization_is_off(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('My Billing')
            ->assertSessionHas(MonetizationSettingsService::SESSION_KEY, false);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertNotFound();
    }

    public function test_member_can_open_my_billing_when_monetization_is_on(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->member();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Billing')
            ->assertSessionHas(MonetizationSettingsService::SESSION_KEY, true);

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('My Billing')
            ->assertSee('Invoice history')
            ->assertSee('No invoices yet');
    }

    public function test_licensed_associate_can_switch_to_annual_plan(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($user);
        $invoice = SubscriptionInvoice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Payment plan')
            ->assertSee('Monthly auto-pay')
            ->assertSee('Annual');

        $this->actingAs($user)
            ->patch(route('billing.plan.update'), ['plan' => 'annual'])
            ->assertRedirect(route('billing.index'));

        $this->assertSame('annual', $user->fresh()->subscription->billing_interval);
        $this->assertSame(119.40, (float) $invoice->fresh()->amount);
    }

    public function test_licensed_associate_sees_invoice_history_on_my_billing(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($user);

        $invoice = SubscriptionInvoice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Active subscription')
            ->assertSee($invoice->number)
            ->assertSee('$9.95')
            ->assertSee('Senior Associate (SA)')
            ->assertSee('Pay now', false)
            ->assertSee('Amount due', false);

        $this->actingAs($user)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($user->name)
            ->assertSee('Total due')
            ->assertSee('$9.95');
    }

    public function test_member_cannot_view_another_associates_invoice(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $owner = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($owner);
        $invoice = SubscriptionInvoice::query()->where('user_id', $owner->id)->firstOrFail();

        $other = $this->member();

        $this->actingAs($other)
            ->get(route('billing.invoices.show', $invoice))
            ->assertForbidden();
    }

    public function test_admin_can_view_another_associates_invoice(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $owner = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($owner);
        $invoice = SubscriptionInvoice::query()->where('user_id', $owner->id)->firstOrFail();

        $admin = $this->member();
        $admin->syncRoles(['admin']);

        $this->actingAs($admin)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($owner->name);
    }

    private function member(): User
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $user->profile()->create([]);

        return $user->fresh(['profile']);
    }

    private function licensedMemberAtRank(string $code): User
    {
        $user = User::factory()->create([
            'rank_id' => Rank::query()->where('code', $code)->value('id'),
        ]);
        $user->assignRole('member');
        $user->profile()->create([
            'license_number' => 'LIC-'.$user->id,
            'is_licensed_life_insurance' => true,
        ]);

        return $user->fresh(['rank', 'profile', 'subscription']);
    }
}
