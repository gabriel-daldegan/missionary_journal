<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionRenewed;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnSubscriptionRenewal implements ShouldQueue
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(SubscriptionRenewed $event): void
    {
        // this is to handle when the subscription has a trial period and the first payment is made on first renewal
        $this->referralService->processReferralOnFirstSubscriptionPayment($event->subscription);
    }
}
