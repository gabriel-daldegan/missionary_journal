<?php

namespace App\Mapper;

use App\Constants\SubscriptionStatus;

class SubscriptionStatusMapper
{
    public function mapForDisplay(string $status)
    {
        return match ($status) {
            SubscriptionStatus::ACTIVE->value => __('Active'),
            SubscriptionStatus::CANCELED->value => __('Canceled'),
            SubscriptionStatus::INACTIVE->value => __('Inactive'),
            SubscriptionStatus::PAST_DUE->value => __('Past Due'),
            SubscriptionStatus::PAUSED->value => __('Paused'),
            SubscriptionStatus::NEW->value => __('New'),
            SubscriptionStatus::PENDING_USER_VERIFICATION->value => __('Pending User Verification'),
            default => __('Pending'),
        };
    }

    public function mapColor(string $status)
    {
        return match ($status) {
            SubscriptionStatus::ACTIVE->value => 'success',
            default => 'warning',
        };
    }
}
