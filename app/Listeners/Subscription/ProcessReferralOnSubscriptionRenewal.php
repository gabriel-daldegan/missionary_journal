<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionRenewed;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnSubscriptionRenewal implements ShouldQueue
{
    /**
     * @var int We delay the event to ensure that the payment is fully processed.
     */
    public $delay = 120;

    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(SubscriptionRenewed $event): void
    {
        // this is to handle when the subscription has a trial period and the first payment is made on first renewal
        $this->referralService->processReferralOnFirstSubscriptionPayment($event->subscription);
    }
}
