<?php

namespace App\Listeners\Order;

use App\Events\Order\Ordered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderNotification implements ShouldQueue
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
    public function handle(Ordered $event): void
    {
        Mail::to($event->order->user->email)
            ->send(new \App\Mail\Order\Ordered($event->order));
    }
}
