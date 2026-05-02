<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\UserInvitedToTenant;
use App\Mail\Tenant\UserInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendUserInvitationNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserInvitedToTenant $event): void
    {
        Mail::to($event->invitation->email)
            ->send(new UserInvitation($event->invitation));
    }
}
