<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMemberRegistered
{
    use Dispatchable;
    use SerializesModels;

    public const TRIGGER = 'new_member_registration';

    public function __construct(public User $member) {}
}
