<?php

declare(strict_types=1);

namespace App\Jobs\Support;

use App\Services\Support\SupportSlaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSupportTicketSlaJob implements ShouldQueue
{
    use Queueable;

    public function handle(SupportSlaService $sla): void
    {
        $sla->checkAllOpenTickets();
    }
}
