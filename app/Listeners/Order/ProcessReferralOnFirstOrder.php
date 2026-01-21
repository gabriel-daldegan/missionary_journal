<?php

namespace App\Listeners\Order;

use App\Events\Order\Ordered;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnFirstOrder implements ShouldQueue
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Ordered $event): void
    {
        $order = $event->order;

        $hasPaidTransaction = $order->transactions()
            ->where('amount', '>', 0)
            ->exists();

        if (! $hasPaidTransaction) {
            return;
        }

        $this->referralService->processFirstPayment($order->user);
    }
}
