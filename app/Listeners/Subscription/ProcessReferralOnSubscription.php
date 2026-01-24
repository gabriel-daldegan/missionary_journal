<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\Subscribed;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnSubscription implements ShouldQueue
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Subscribed $event): void
    {
        $this->referralService->processReferralOnFirstSubscriptionPayment($event->subscription);
    }
}
