<?php

namespace App\Events;

use App\Models\Backup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Backup $backup)
    {
    }
}
