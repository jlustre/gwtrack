<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\NewMemberValidatedTaskService;
use Illuminate\Auth\Events\Verified;

class HandleNewMemberEmailVerified
{
    public function __construct(
        private readonly NewMemberValidatedTaskService $validatedTasks,
    ) {}

    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $this->validatedTasks->assignAll($user->fresh(['profile', 'sponsor', 'roles']));
    }
}
