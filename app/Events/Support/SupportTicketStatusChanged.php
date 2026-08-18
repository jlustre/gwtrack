<?php

declare(strict_types=1);

namespace App\Events\Support;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public User $actor,
    ) {}
}
