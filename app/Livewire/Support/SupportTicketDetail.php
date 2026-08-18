<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SupportTicketDetail extends Component
{
    public SupportTicket $ticket;

    public string $comment = '';

    public function mount(SupportTicket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket->load(['status', 'assignee', 'comments.user', 'attachments']);
    }

    public function postComment(SupportTicketService $tickets): void
    {
        $this->authorize('comment', $this->ticket);

        $this->validate([
            'comment' => ['required', 'string', 'min:2'],
        ]);

        $isAgent = auth()->user()->can('manage support tickets');

        $tickets->addComment($this->ticket, auth()->user(), $this->comment, $isAgent);
        $this->comment = '';
        $this->ticket->refresh()->load(['status', 'assignee', 'comments.user', 'attachments']);

        session()->flash('support_status', 'Reply posted.');
    }

    public function reopen(SupportTicketService $tickets): void
    {
        $this->authorize('reopen', $this->ticket);

        $tickets->reopenTicket($this->ticket, auth()->user());
        $this->ticket->refresh()->load(['status', 'assignee', 'comments.user', 'attachments']);

        session()->flash('support_status', 'Ticket reopened.');
    }

    public function render(): View
    {
        return view('livewire.support.support-ticket-detail');
    }
}
