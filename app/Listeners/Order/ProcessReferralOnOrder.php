<?php

namespace App\Listeners\Order;

use App\Events\Order\Ordered;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessReferralOnOrder implements ShouldQueue
{
    /**
     * @var int We delay the event to ensure that the payment is fully processed.
     */
    public $delay = 120;

    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Ordered $event): void
    {
        $this->referralService->processReferralOnFirstOrderPayment($event->order);
    }
}
