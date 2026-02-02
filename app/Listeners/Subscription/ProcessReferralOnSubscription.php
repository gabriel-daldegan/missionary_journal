<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\Subscribed;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnSubscription implements ShouldQueue
{
    /**
     * @var int We delay the event to ensure that the payment is fully processed.
     */
    public $delay = 120;

    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Subscribed $event): void
    {
        $this->referralService->processReferralOnFirstSubscriptionPayment($event->subscription);
    }
}
