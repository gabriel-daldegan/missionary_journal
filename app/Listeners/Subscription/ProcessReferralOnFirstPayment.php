<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\Subscribed;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnFirstPayment implements ShouldQueue
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Subscribed $event): void
    {
        $subscription = $event->subscription;

        $hasPaidTransaction = $subscription->transactions()
            ->where('amount', '>', 0)
            ->exists();

        if (! $hasPaidTransaction) {
            return;
        }

        $this->referralService->processFirstPayment($subscription->user);
    }
}
