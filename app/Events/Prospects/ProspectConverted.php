<?php

namespace App\Events\Prospects;

use App\Models\Prospect;
use App\Models\ProspectConversion;
use App\Models\RegistrationInvitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProspectConverted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Prospect $prospect,
        public User $actor,
        public ProspectConversion $conversion,
        public string $phase = 'initiated',
        public ?RegistrationInvitation $invitation = null,
    ) {}
}
