<?php

namespace App\Events\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProspectStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Prospect $prospect,
        public User $actor,
        public ?int $fromStageId,
        public int $toStageId,
        public string $source,
    ) {}
}
