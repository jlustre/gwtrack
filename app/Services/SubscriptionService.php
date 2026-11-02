<?php

namespace App\Services;

use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function __construct(
        private readonly MonetizationSettingsService $settings,
        private readonly InvoicePaymentService $payments,
    ) {}

    public function enabled(): bool
    {
        return $this->settings->enabled();
    }

    public function amountForRank(?string $code): float
    {
        return $this->settings->amountForRank($code);
    }

    public function amountForInterval(?string $code, string $interval): float
    {
        return $this->settings->amountForInterval($code, $interval);
    }

    public function associateIsLicensed(User $user): bool
    {
        $user->loadMissing('profile');

        return (bool) $user->profile?->isLicensedAssociate();
    }

    public function currentRankCode(User $user): ?string
    {
        $user->loadMissing('rank');

        return $user->rank?->code;
    }

    public function isBillable(User $user): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (! $this->associateIsLicensed($user)) {
            return false;
        }

        return $this->amountForRank($this->currentRankCode($user)) > 0;
    }

    public function currentCharge(User $user, ?CarbonInterface $asOf = null): float
    {
        if (! $this->enabled() || ! $this->associateIsLicensed($user)) {
            return 0.0;
        }

        $subscription = $this->sync($user, $asOf);

        return $this->amountForInterval($subscription->billed_rank_code, $subscription->billingInterval());
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user, ?CarbonInterface $asOf = null): array
    {
        $licensed = $this->associateIsLicensed($user);
        $currentRank = $this->currentRankCode($user);
        $subscription = $this->enabled() ? $this->sync($user, $asOf) : $user->subscription;

        $billedRank = $subscription?->billed_rank_code;
        $pendingRank = $subscription?->pending_rank_code;
        $interval = $subscription?->billingInterval() ?? UserSubscription::INTERVAL_MONTHLY;
        $pendingInterval = $subscription?->pending_billing_interval;
        $monthly = ($this->enabled() && $licensed) ? $this->amountForRank($billedRank) : 0.0;
        $pendingMonthly = ($this->enabled() && $licensed) ? $this->amountForRank($pendingRank ?? $billedRank) : 0.0;
        $billedAmount = ($this->enabled() && $licensed) ? $this->amountForInterval($billedRank, $interval) : 0.0;
        $annualAmount = ($this->enabled() && $licensed) ? $this->amountForInterval($billedRank ?? $currentRank, UserSubscription::INTERVAL_ANNUAL) : 0.0;
        $effectiveInterval = $pendingInterval ?: $interval;

        return [
            'enabled' => $this->enabled(),
            'licensed' => $licensed,
            'billable' => $this->isBillable($user),
            'current_rank' => $currentRank,
            'current_rank_label' => $this->settings->rankLabel($currentRank),
            'billed_rank' => $billedRank,
            'billed_rank_label' => $billedRank ? $this->settings->rankLabel($billedRank) : null,
            'pending_rank' => $pendingRank,
            'pending_rank_label' => $pendingRank ? $this->settings->rankLabel($pendingRank) : null,
            'monthly_amount' => $monthly,
            'monthly_amount_label' => '$'.number_format($monthly, 2),
            'annual_amount' => $annualAmount,
            'annual_amount_label' => '$'.number_format($annualAmount, 2),
            'billed_amount' => $billedAmount,
            'billed_amount_label' => '$'.number_format($billedAmount, 2),
            'pending_amount' => $pendingMonthly,
            'pending_amount_label' => '$'.number_format($pendingMonthly, 2),
            'billing_interval' => $interval,
            'billing_interval_label' => UserSubscription::intervalLabel($interval),
            'pending_billing_interval' => $pendingInterval,
            'pending_billing_interval_label' => $pendingInterval ? UserSubscription::intervalLabel($pendingInterval) : null,
            'plan_key' => $effectiveInterval === UserSubscription::INTERVAL_ANNUAL ? 'annual' : 'monthly_auto',
            'auto_pay' => (bool) ($subscription?->auto_pay),
            'auto_pay_ready' => (bool) ($subscription?->hasAutoPayCard()),
            'auto_pay_card_label' => $subscription?->autoPayCardLabel(),
            'next_billing_at' => $subscription?->next_billing_at?->toDateString(),
            'started_at' => $subscription?->started_at?->toDateString(),
            'status_label' => $this->statusLabel($licensed, $monthly, $interval, (bool) ($subscription?->auto_pay)),
            'status_detail' => $this->statusDetail($licensed, $currentRank, $monthly, $interval, $pendingInterval, (bool) ($subscription?->auto_pay), (bool) ($subscription?->hasAutoPayCard())),
        ];
    }

    /**
     * @return Collection<int, SubscriptionInvoice>
     */
    public function invoicesFor(User $user): Collection
    {
        return SubscriptionInvoice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('billed_at')
            ->orderByDesc('id')
            ->get();
    }

    public function updatePlan(User $user, string $plan): UserSubscription
    {
        abort_unless(in_array($plan, ['monthly_auto', 'annual'], true), 422);

        $subscription = $this->sync($user);
        $interval = $plan === 'annual'
            ? UserSubscription::INTERVAL_ANNUAL
            : UserSubscription::INTERVAL_MONTHLY;
        $autoPay = $plan === 'monthly_auto';

        $subscription->auto_pay = $autoPay;

        if ($interval === $subscription->billingInterval()) {
            $subscription->pending_billing_interval = null;

            return $this->persist($subscription);
        }

        $openInvoice = $this->openInvoiceFor($subscription);

        if ($openInvoice !== null) {
            $subscription->billing_interval = $interval;
            $subscription->pending_billing_interval = null;
            $this->persist($subscription);
            $this->rewriteOpenInvoice($subscription, $openInvoice);

            return $subscription->fresh() ?? $subscription;
        }

        $subscription->pending_billing_interval = $interval;

        return $this->persist($subscription);
    }

    public function sync(User $user, ?CarbonInterface $asOf = null): UserSubscription
    {
        $asOf ??= now();
        $subscription = $this->subscriptionFor($user);

        if (! $this->enabled()) {
            return $subscription;
        }

        $this->applyDueAnniversaries($subscription, $asOf);

        $currentRank = $this->currentRankCode($user);

        if ($currentRank === null) {
            return $this->persist($subscription);
        }

        if ($subscription->billed_rank_code === null && $this->associateIsLicensed($user) && $this->amountForRank($currentRank) > 0) {
            $this->startBilling($subscription, $currentRank, $asOf);
            $this->persist($subscription);
            $this->issueInvoice($subscription, $currentRank, $asOf, $subscription->next_billing_at);

            return $subscription;
        }

        if ($subscription->billed_rank_code !== null) {
            $subscription->pending_rank_code = $currentRank === $subscription->billed_rank_code
                ? null
                : $currentRank;
        }

        return $this->persist($subscription);
    }

    public function applyDueAnniversariesForAll(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();
        $updated = 0;

        UserSubscription::query()
            ->with('user.profile')
            ->whereNotNull('next_billing_at')
            ->whereDate('next_billing_at', '<=', $asOf->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($asOf, &$updated): void {
                foreach ($subscriptions as $subscription) {
                    $this->applyDueAnniversaries($subscription, $asOf);
                    $this->persist($subscription);
                    $updated++;
                }
            });

        return $updated;
    }

    private function subscriptionFor(User $user): UserSubscription
    {
        $user->loadMissing('subscription');

        if ($user->subscription) {
            return $user->subscription;
        }

        $subscription = new UserSubscription([
            'user_id' => $user->id,
            'billing_interval' => UserSubscription::INTERVAL_MONTHLY,
            'auto_pay' => false,
        ]);
        $user->setRelation('subscription', $subscription);

        return $subscription;
    }

    private function startBilling(UserSubscription $subscription, string $rankCode, CarbonInterface $asOf): void
    {
        $day = $this->clampAnniversaryDay($asOf->day);

        $subscription->billed_rank_code = $rankCode;
        $subscription->pending_rank_code = null;
        $subscription->billing_interval = $subscription->billing_interval ?: UserSubscription::INTERVAL_MONTHLY;
        $subscription->anniversary_day = $day;
        $subscription->started_at ??= $asOf->copy()->startOfDay();
        $subscription->next_billing_at = $this->nextAnniversary($asOf, $day, $subscription->billingInterval());
    }

    private function applyDueAnniversaries(UserSubscription $subscription, CarbonInterface $asOf): void
    {
        if ($subscription->next_billing_at === null || $subscription->billed_rank_code === null) {
            return;
        }

        $safety = 0;

        while ($subscription->next_billing_at !== null
            && $subscription->next_billing_at->lte($asOf->copy()->startOfDay())
            && $safety < 36
        ) {
            $periodStart = $subscription->next_billing_at->copy();

            if (filled($subscription->pending_rank_code)) {
                $subscription->billed_rank_code = $subscription->pending_rank_code;
                $subscription->pending_rank_code = null;
            }

            if (filled($subscription->pending_billing_interval)) {
                $subscription->billing_interval = $subscription->pending_billing_interval;
                $subscription->pending_billing_interval = null;
            }

            $day = $subscription->anniversary_day ?? $this->clampAnniversaryDay($subscription->next_billing_at->day);
            $subscription->anniversary_day = $day;
            $subscription->next_billing_at = $this->nextAnniversary($subscription->next_billing_at, $day, $subscription->billingInterval());
            $subscription->last_anniversary_applied_at = $asOf;
            $this->persist($subscription);
            $invoice = $this->issueInvoice($subscription, (string) $subscription->billed_rank_code, $periodStart, $subscription->next_billing_at);

            if ($invoice !== null) {
                $this->payments->applyStoredAutoPay($invoice, $subscription);
            }

            $safety++;
        }
    }

    private function nextAnniversary(CarbonInterface $from, int $day, string $interval): Carbon
    {
        $day = $this->clampAnniversaryDay($day);
        $cursor = Carbon::parse($from)->startOfDay();
        $cursor = $interval === UserSubscription::INTERVAL_ANNUAL
            ? $cursor->addYearNoOverflow()
            : $cursor->addMonthNoOverflow();

        return $cursor->day(min($day, $cursor->daysInMonth));
    }

    private function clampAnniversaryDay(int $day): int
    {
        return min(28, max(1, $day));
    }

    private function persist(UserSubscription $subscription): UserSubscription
    {
        if (! $subscription->user_id) {
            return $subscription;
        }

        if (
            ! $subscription->exists
            && $subscription->billed_rank_code === null
            && $subscription->pending_rank_code === null
        ) {
            return $subscription;
        }

        if ($subscription->isDirty() || ! $subscription->exists) {
            $subscription->save();
        }

        return $subscription;
    }

    private function openInvoiceFor(UserSubscription $subscription): ?SubscriptionInvoice
    {
        if (! $subscription->user_id) {
            return null;
        }

        return SubscriptionInvoice::query()
            ->where('user_id', $subscription->user_id)
            ->where('status', SubscriptionInvoice::STATUS_ISSUED)
            ->orderByDesc('billed_at')
            ->orderByDesc('id')
            ->first();
    }

    private function rewriteOpenInvoice(UserSubscription $subscription, SubscriptionInvoice $invoice): void
    {
        $interval = $subscription->billingInterval();
        $rankCode = $invoice->rank_code ?: (string) $subscription->billed_rank_code;
        $amount = $this->amountForInterval($rankCode, $interval);
        $day = $subscription->anniversary_day ?? $this->clampAnniversaryDay($invoice->period_start->day);
        $next = $this->nextAnniversary($invoice->period_start, $day, $interval);
        $periodEnd = $next->copy()->startOfDay()->subDay();

        if ($periodEnd->lt($invoice->period_start)) {
            $periodEnd = $invoice->period_start->copy();
        }

        $rankLabel = $this->settings->rankLabel($rankCode);

        $invoice->forceFill([
            'billing_interval' => $interval,
            'amount' => $amount,
            'period_end' => $periodEnd,
            'description' => $this->invoiceDescription($interval, $rankLabel),
        ])->save();

        $subscription->anniversary_day = $day;
        $subscription->next_billing_at = $next;
        $this->persist($subscription);
    }

    private function issueInvoice(
        UserSubscription $subscription,
        string $rankCode,
        CarbonInterface $billedAt,
        ?CarbonInterface $nextBillingAt,
    ): ?SubscriptionInvoice {
        if (! $subscription->user_id || $nextBillingAt === null) {
            return null;
        }

        $subscription->loadMissing('user.profile');
        $user = $subscription->user;

        if ($user === null || ! $this->associateIsLicensed($user)) {
            return null;
        }

        $interval = $subscription->billingInterval();
        $amount = $this->amountForInterval($rankCode, $interval);

        if ($amount <= 0) {
            return null;
        }

        $periodStart = $billedAt->copy()->startOfDay();
        $periodEnd = $nextBillingAt->copy()->startOfDay()->subDay();

        if ($periodEnd->lt($periodStart)) {
            $periodEnd = $periodStart->copy();
        }

        $alreadyIssued = SubscriptionInvoice::query()
            ->where('user_id', $subscription->user_id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->exists();

        if ($alreadyIssued) {
            return null;
        }

        $rankLabel = $this->settings->rankLabel($rankCode);

        $invoice = SubscriptionInvoice::query()->create([
            'user_id' => $subscription->user_id,
            'user_subscription_id' => $subscription->id,
            'number' => 'TMP-'.(string) \Illuminate\Support\Str::ulid(),
            'rank_code' => $rankCode,
            'rank_label' => $rankLabel,
            'billing_interval' => $interval,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => SubscriptionInvoice::STATUS_ISSUED,
            'billed_at' => $periodStart,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'description' => $this->invoiceDescription($interval, $rankLabel),
        ]);

        $invoice->forceFill([
            'number' => sprintf('GW-%s-%06d', $periodStart->format('Y'), $invoice->id),
        ])->save();

        return $invoice->fresh();
    }

    private function invoiceDescription(string $interval, string $rankLabel): string
    {
        $term = $interval === UserSubscription::INTERVAL_ANNUAL ? 'annual' : 'monthly';

        return 'GWTracks '.$term.' subscription — '.$rankLabel;
    }

    private function statusLabel(bool $licensed, float $monthly, string $interval, bool $autoPay): string
    {
        if (! $this->enabled()) {
            return 'Not billing';
        }

        if (! $licensed) {
            return 'Free while unlicensed';
        }

        if ($monthly <= 0) {
            return 'No monthly charge';
        }

        if ($interval === UserSubscription::INTERVAL_ANNUAL) {
            return 'Annual plan';
        }

        return $autoPay ? 'Monthly auto-pay' : 'Active subscription';
    }

    private function statusDetail(
        bool $licensed,
        ?string $currentRank,
        float $monthly,
        string $interval,
        ?string $pendingInterval,
        bool $autoPay,
        bool $autoPayReady,
    ): string {
        if (! $this->enabled()) {
            return 'Subscription billing is currently off. GWTracks is free for all associates.';
        }

        if (! $licensed) {
            return 'You are not billed until your life license is on file. Rank prices apply after you are licensed and reach Senior Associate or above.';
        }

        if ($monthly <= 0) {
            $label = $this->settings->rankLabel($currentRank);

            return $label.' stays free. Invoices begin when you reach Senior Associate or another billed rank.';
        }

        $parts = [];

        if ($interval === UserSubscription::INTERVAL_ANNUAL) {
            $parts[] = 'You are on the annual plan: one invoice covers twelve months of the rank price.';
        } elseif ($autoPay && $autoPayReady) {
            $parts[] = 'Monthly auto-pay is on. New invoices are marked paid when they are issued.';
        } elseif ($autoPay) {
            $parts[] = 'Monthly auto-pay is selected. Pay an invoice with a card to start automatic monthly charges.';
        } else {
            $parts[] = 'Invoices are issued on your subscription anniversary.';
        }

        if ($pendingInterval) {
            $parts[] = 'Your switch to '.strtolower(UserSubscription::intervalLabel($pendingInterval)).' billing starts on the next billing date.';
        }

        $parts[] = 'A rank change takes effect on the next billing date, not the day of the promotion.';

        return implode(' ', $parts);
    }
}
