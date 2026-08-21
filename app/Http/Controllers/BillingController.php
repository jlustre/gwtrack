<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayInvoiceRequest;
use App\Http\Requests\UpdateBillingPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\UserSubscription;
use App\Services\InvoicePaymentService;
use App\Services\MonetizationSettingsService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions, MonetizationSettingsService $monetization): View
    {
        abort_unless($monetization->sessionEnabled(), 404);

        $user = $request->user();
        $snapshot = $subscriptions->snapshot($user);
        $invoices = $subscriptions->invoicesFor($user);
        $outstanding = $invoices->where('status', SubscriptionInvoice::STATUS_ISSUED);

        return view('billing.index', [
            'user' => $user,
            'snapshot' => $snapshot,
            'invoices' => $invoices,
            'outstandingAmount' => round((float) $outstanding->sum('amount'), 2),
            'outstandingCount' => $outstanding->count(),
            'totalCollected' => round((float) $invoices->where('status', SubscriptionInvoice::STATUS_PAID)->sum('amount'), 2),
        ]);
    }

    public function show(Request $request, SubscriptionInvoice $invoice, MonetizationSettingsService $monetization): View
    {
        abort_unless($monetization->sessionEnabled(), 404);

        $viewer = $request->user();

        abort_unless(
            $invoice->user_id === $viewer->id
                || $viewer->hasAnyRole(['super-admin', 'admin', 'agency-owner']),
            403
        );

        $invoice->loadMissing(['user.profile', 'user.rank', 'payer']);

        return view('billing.invoice', [
            'invoice' => $invoice,
            'associate' => $invoice->user,
        ]);
    }

    public function pay(Request $request, SubscriptionInvoice $invoice, MonetizationSettingsService $monetization, InvoicePaymentService $payments): View
    {
        abort_unless($monetization->sessionEnabled(), 404);
        abort_unless($payments->canPayInvoice($request->user(), $invoice), 403);

        $invoice->loadMissing(['user.profile', 'user.rank', 'user.subscription']);

        return view('billing.pay', [
            'invoice' => $invoice,
            'associate' => $invoice->user,
            'enableAutoPayDefault' => (bool) ($invoice->user?->subscription?->auto_pay
                || ($invoice->billing_interval ?: UserSubscription::INTERVAL_MONTHLY) === UserSubscription::INTERVAL_MONTHLY),
        ]);
    }

    public function updatePlan(
        UpdateBillingPlanRequest $request,
        SubscriptionService $subscriptions,
        MonetizationSettingsService $monetization,
    ): RedirectResponse {
        abort_unless($monetization->sessionEnabled(), 404);

        $plan = (string) $request->validated('plan');
        $subscriptions->updatePlan($request->user(), $plan);

        $message = $plan === 'annual'
            ? 'Annual billing selected. Your open invoice now covers twelve months, or the change starts on the next billing date if this period is already paid.'
            : 'Monthly auto-pay selected. Pay an invoice with a card to start automatic monthly charges, or they will start on the next invoice if a card is already on file.';

        return redirect()
            ->route('billing.index')
            ->with('billing_status', $message);
    }

    public function processPayment(
        PayInvoiceRequest $request,
        SubscriptionInvoice $invoice,
        InvoicePaymentService $payments,
    ): RedirectResponse {
        abort_unless($invoice->isPayable(), 422);

        $payments->payWithCard(
            $invoice,
            $request->user(),
            (string) $request->validated('card_number'),
            (bool) $request->boolean('enable_auto_pay'),
        );

        $message = $request->boolean('enable_auto_pay')
            ? 'Payment received. Automatic payments are on for future invoices.'
            : 'Payment received. Thank you — this invoice is paid.';

        return redirect()
            ->route('billing.invoices.show', $invoice)
            ->with('billing_status', $message);
    }
}
