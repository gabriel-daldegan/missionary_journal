<?php

namespace App\Listeners\User;

use App\Services\ReferralService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnVerification implements ShouldQueue
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Verified $event): void
    {
        $this->referralService->processVerifiedRegistration($event->user);
    }
}
