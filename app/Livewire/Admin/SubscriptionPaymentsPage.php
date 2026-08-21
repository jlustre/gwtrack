<?php

namespace App\Livewire\Admin;

use App\Models\SubscriptionInvoice;
use App\Services\InvoicePaymentService;
use App\Services\MonetizationSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionPaymentsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'outstanding';

    #[Url]
    public string $search = '';

    public bool $showRecordForm = false;

    public ?int $recordingId = null;

    public string $recordingNumber = '';

    public string $recordingAmountLabel = '';

    public string $paymentMethod = SubscriptionInvoice::METHOD_CARD;

    public string $paymentReference = '';

    public string $paymentNotes = '';

    public function mount(MonetizationSettingsService $monetization, InvoicePaymentService $payments): void
    {
        abort_unless($monetization->sessionEnabled(), 404);
        abort_unless($payments->canProcessPayments(auth()->user()), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function openRecordForm(int $invoiceId, InvoicePaymentService $payments): void
    {
        abort_unless($payments->canProcessPayments(auth()->user()), 403);

        $invoice = SubscriptionInvoice::query()->findOrFail($invoiceId);
        abort_unless($invoice->isPayable(), 422);

        $this->recordingId = $invoice->id;
        $this->recordingNumber = $invoice->number;
        $this->recordingAmountLabel = $invoice->amountLabel();
        $this->paymentMethod = SubscriptionInvoice::METHOD_CARD;
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->showRecordForm = true;
        $this->resetValidation();
    }

    public function cancelRecordForm(): void
    {
        $this->showRecordForm = false;
        $this->recordingId = null;
        $this->recordingNumber = '';
        $this->recordingAmountLabel = '';
        $this->paymentMethod = SubscriptionInvoice::METHOD_CARD;
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->resetValidation();
    }

    public function recordPayment(InvoicePaymentService $payments): void
    {
        abort_unless($payments->canProcessPayments(auth()->user()), 403);
        abort_unless($this->recordingId, 422);

        $validated = $this->validate([
            'paymentMethod' => ['required', 'in:'.implode(',', array_keys(SubscriptionInvoice::paymentMethodOptions()))],
            'paymentReference' => ['nullable', 'string', 'max:80'],
            'paymentNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = SubscriptionInvoice::query()->findOrFail($this->recordingId);

        $payments->recordPayment(
            $invoice,
            auth()->user(),
            $validated['paymentMethod'],
            $validated['paymentReference'] ?? null,
            $validated['paymentNotes'] ?? null,
        );

        $this->cancelRecordForm();
        session()->flash('payments_status', 'Payment recorded for '.$invoice->number.'.');
    }

    public function voidInvoice(int $invoiceId, InvoicePaymentService $payments): void
    {
        abort_unless($payments->canProcessPayments(auth()->user()), 403);

        $invoice = SubscriptionInvoice::query()->findOrFail($invoiceId);
        $payments->voidInvoice($invoice, auth()->user());

        session()->flash('payments_status', $invoice->number.' was voided.');
    }

    public function render(InvoicePaymentService $payments): View
    {
        $tab = in_array($this->tab, ['outstanding', 'paid', 'all'], true) ? $this->tab : 'outstanding';

        return view('livewire.admin.subscription-payments-page', [
            'stats' => $payments->stats(),
            'invoices' => $payments->invoicesQuery($tab, $this->search)->paginate(20),
            'methods' => SubscriptionInvoice::paymentMethodOptions(),
        ])->layout('layouts.app');
    }
}
