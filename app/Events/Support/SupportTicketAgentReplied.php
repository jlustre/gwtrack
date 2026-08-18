<?php

declare(strict_types=1);

namespace App\Events\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketAgentReplied
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportTicketComment $comment,
        public User $agent,
    ) {}
}
