<?php

namespace App\Events\Referral;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $referrer,
        public User $referredUser,
        public Referral $referral
    ) {}
}
