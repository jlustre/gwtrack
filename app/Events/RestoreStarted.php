<?php

namespace App\Events;

use App\Models\RestoreLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestoreStarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RestoreLog $restoreLog)
    {
    }
}
