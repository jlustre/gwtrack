<?php

namespace App\Events\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FnaRevisionRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FnaRecord $fna,
        public User $reviewedBy,
        public string $comment,
    ) {}
}
