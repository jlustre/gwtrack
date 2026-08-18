<?php

namespace App\Listeners;

use App\Events\NewMemberRegistered;
use App\Services\NewMemberRegistrationService;

class HandleNewMemberRegistration
{
    public function __construct(
        private readonly NewMemberRegistrationService $registrationService,
    ) {}

    public function handle(NewMemberRegistered $event): void
    {
        $this->registrationService->process($event->member);
    }
}
