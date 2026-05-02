<?php

namespace App\Listeners\User;

use App\Events\User\UserPhoneVerified;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivateSubscriptionsPendingUserVerification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserPhoneVerified $event): void
    {
        $this->subscriptionService->activateSubscriptionsPendingUserVerification($event->user);
    }
}
