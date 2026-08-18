<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketStatus;
use App\Models\User;
use App\Services\Support\SupportDashboardMetricsService;
use App\Services\Support\SupportTicketService;
use App\Services\Support\SupportWishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AdminSupportDashboard extends Component
{
    use WithPagination;

    public string $global_search = '';

    public string $filter_status = '';

    public string $filter_module = '';

    public string $filter_urgency = '';

    public bool $toggle_sla_breach_only = false;

    public ?int $selectedTicketId = null;

    public string $internal_note = '';

    public string $agent_reply = '';

    public ?int $assign_to = null;

    public ?int $new_status_id = null;

    protected $queryString = [
        'global_search' => ['except' => ''],
        'filter_status' => ['except' => ''],
        'filter_module' => ['except' => ''],
        'filter_urgency' => ['except' => ''],
        'toggle_sla_breach_only' => ['except' => false],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view all support tickets'), 403);
    }

    public function updatingGlobalSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterModule(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUrgency(): void
    {
        $this->resetPage();
    }

    public function updatingToggleSlaBreachOnly(): void
    {
        $this->resetPage();
    }

    public function openTicket(int $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
        $ticket = SupportTicket::with(['status', 'assignee'])->findOrFail($ticketId);
        $this->new_status_id = $ticket->status_id;
        $this->assign_to = $ticket->assigned_to;
    }

    public function closeTicketModal(): void
    {
        $this->selectedTicketId = null;
        $this->internal_note = '';
        $this->agent_reply = '';
    }

    public function saveTicketActions(
        SupportTicketService $tickets,
        SupportWishlistService $wishlist,
    ): void {
        abort_unless(auth()->user()?->can('manage support tickets'), 403);

        $ticket = SupportTicket::with('status')->findOrFail($this->selectedTicketId);

        if ($this->assign_to && auth()->user()->can('assign support tickets')) {
            $assignee = User::findOrFail($this->assign_to);
            $tickets->assignTicket($ticket, auth()->user(), $assignee);
        }

        if ($this->new_status_id && auth()->user()->can('update support ticket status')) {
            $status = SupportTicketStatus::findOrFail($this->new_status_id);
            $tickets->updateStatus($ticket, $status, auth()->user());
        }

        if (trim($this->internal_note) !== '' && auth()->user()->can('add internal support notes')) {
            $tickets->addInternalNote($ticket, auth()->user(), $this->internal_note);
            $this->internal_note = '';
        }

        if (trim($this->agent_reply) !== '') {
            $tickets->addComment($ticket, auth()->user(), $this->agent_reply, isAgent: true);
            $this->agent_reply = '';
        }

        session()->flash('support_admin_status', 'Ticket updated.');
        $this->dispatch('support-dashboard-refresh');
    }

    public function convertToWishlist(SupportWishlistService $wishlist): void
    {
        abort_unless(auth()->user()?->can('manage enhancement wishlist'), 403);

        $ticket = SupportTicket::findOrFail($this->selectedTicketId);
        $wishlist->convertTicketToWishlist($ticket, auth()->user());

        session()->flash('support_admin_status', 'Ticket converted to enhancement wishlist item.');
    }

    public function render(
        SupportTicketService $tickets,
        SupportDashboardMetricsService $metrics,
    ): View {
        $ticketList = $tickets->paginateForAdmin([
            'global_search' => $this->global_search,
            'filter_status' => $this->filter_status,
            'filter_module' => $this->filter_module,
            'filter_urgency' => $this->filter_urgency,
            'toggle_sla_breach_only' => $this->toggle_sla_breach_only,
        ]);

        $selectedTicket = $this->selectedTicketId
            ? SupportTicket::with(['user', 'assignee', 'status', 'comments.user', 'internalNotes.user', 'attachments'])->find($this->selectedTicketId)
            : null;

        return view('livewire.support.admin-support-dashboard', [
            'metrics' => $metrics->metrics(),
            'tickets' => $ticketList,
            'statuses' => SupportTicketStatus::query()->orderBy('sort_order')->get(),
            'modules' => config('support.modules', []),
            'urgencyLevels' => config('support.urgency_levels', []),
            'agents' => User::role(['support-agent', 'admin', 'super-admin'])->orderBy('name')->get(),
            'selectedTicket' => $selectedTicket,
        ]);
    }
}
