<?php

namespace App\Constants;

class SubscriptionConstants
{
    public const SUBSCRIPTION_STATUS_THAT_ARE_NOT_DEAD = [
        SubscriptionStatus::PENDING->value,
        SubscriptionStatus::ACTIVE->value,
        SubscriptionStatus::PAUSED->value,
        SubscriptionStatus::PAST_DUE->value,
        SubscriptionStatus::PENDING_USER_VERIFICATION->value,
    ];
}
