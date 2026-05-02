<?php

namespace App\Listeners\User;

use App\Events\User\UserSeen;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserLastSeen implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private UserService $userService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserSeen $userSeenEvent): void
    {
        $this->userService->updateUserLastSeen($userSeenEvent->user);
    }
}
