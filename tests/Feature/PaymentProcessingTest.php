<?php

namespace Tests\Feature;

use App\Livewire\Admin\SubscriptionPaymentsPage;
use App\Models\Rank;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Services\MonetizationSettingsService;
use App\Services\SubscriptionService;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
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

    public function test_member_can_open_pay_invoice_page_when_billing_is_on(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($user);
        $invoice = SubscriptionInvoice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Pay now', false)
            ->assertSee('Amount due', false);

        $this->actingAs($user)
            ->get(route('billing.invoices.pay', $invoice))
            ->assertOk()
            ->assertSee('Pay invoice', false)
            ->assertSee($invoice->number)
            ->assertSee('Pay $9.95');
    }

    public function test_member_can_pay_open_invoice_with_card(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($user);
        $invoice = SubscriptionInvoice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('billing.invoices.pay.store', $invoice), [
                'cardholder_name' => $user->name,
                'card_number' => '4242424242424242',
                'card_expiry' => '12/29',
                'card_cvc' => '123',
                'enable_auto_pay' => '1',
            ])
            ->assertRedirect(route('billing.invoices.show', $invoice))
            ->assertSessionHas('billing_status');

        $invoice = $invoice->fresh();

        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->status);
        $this->assertSame(SubscriptionInvoice::METHOD_CARD, $invoice->payment_method);
        $this->assertSame('Visa •••• 4242', $invoice->payment_reference);
        $this->assertSame($user->id, $invoice->paid_by);
        $this->assertNotNull($invoice->paid_at);
        $this->assertNull($invoice->payment_notes);

        $subscription = $user->fresh()->subscription;
        $this->assertTrue((bool) $subscription->auto_pay);
        $this->assertSame('Visa', $subscription->auto_pay_brand);
        $this->assertSame('4242', $subscription->auto_pay_last_four);

        $this->actingAs($user)
            ->get(route('billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Visa •••• 4242', false)
            ->assertSee('Total paid', false)
            ->assertDontSee('Pay $9.95');
    }

    public function test_invalid_card_number_is_rejected(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($user);
        $invoice = SubscriptionInvoice::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->from(route('billing.invoices.pay', $invoice))
            ->post(route('billing.invoices.pay.store', $invoice), [
                'cardholder_name' => $user->name,
                'card_number' => '4242424242424241',
                'card_expiry' => '12/29',
                'card_cvc' => '123',
            ])
            ->assertRedirect(route('billing.invoices.pay', $invoice))
            ->assertSessionHasErrors('card_number');

        $this->assertSame(SubscriptionInvoice::STATUS_ISSUED, $invoice->fresh()->status);
    }

    public function test_member_cannot_pay_another_associates_invoice(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $owner = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($owner);
        $invoice = SubscriptionInvoice::query()->where('user_id', $owner->id)->firstOrFail();

        $other = $this->member();

        $this->actingAs($other)
            ->get(route('billing.invoices.pay', $invoice))
            ->assertForbidden();
    }

    public function test_admin_can_open_subscription_payments_and_record_payment(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $owner = $this->licensedMemberAtRank('SA');
        app(SubscriptionService::class)->currentCharge($owner);
        $invoice = SubscriptionInvoice::query()->where('user_id', $owner->id)->firstOrFail();

        $admin = $this->member();
        $admin->syncRoles(['admin']);

        $this->actingAs($admin)
            ->get(route('admin.payments'))
            ->assertOk()
            ->assertSee('Subscription Payments', false)
            ->assertSee($invoice->number)
            ->assertSee($owner->name);

        Livewire::actingAs($admin)
            ->test(SubscriptionPaymentsPage::class)
            ->assertSee($invoice->number)
            ->call('openRecordForm', $invoice->id)
            ->set('paymentMethod', SubscriptionInvoice::METHOD_CHECK)
            ->set('paymentReference', 'CHK-4411')
            ->set('paymentNotes', 'Received at the agency office.')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $invoice = $invoice->fresh();

        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->status);
        $this->assertSame(SubscriptionInvoice::METHOD_CHECK, $invoice->payment_method);
        $this->assertSame('CHK-4411', $invoice->payment_reference);
        $this->assertSame($admin->id, $invoice->paid_by);
    }

    public function test_subscription_payments_is_hidden_when_billing_is_off(): void
    {
        $admin = $this->member();
        $admin->syncRoles(['admin']);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee('Subscription Payments', false);

        $this->actingAs($admin)
            ->get(route('admin.payments'))
            ->assertNotFound();
    }

    public function test_member_cannot_open_subscription_payments(): void
    {
        app(MonetizationSettingsService::class)->save(true, []);

        $user = $this->member();

        $this->actingAs($user)
            ->get(route('admin.payments'))
            ->assertForbidden();
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
