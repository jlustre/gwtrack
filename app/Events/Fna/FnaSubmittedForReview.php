<?php

namespace App\Events\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FnaSubmittedForReview
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FnaRecord $fna,
        public User $submittedBy,
        public ?User $cfm,
    ) {}
}
