<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentProviders\PaymentService;

class SubscriptionDiscountService
{
    public function __construct(
        private DiscountService $discountService,
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService,
    ) {}

    public function applyDiscount(Subscription $subscription, string $discountCode, User $user): bool
    {
        if (! $this->subscriptionService->canAddDiscount($subscription) ||
            ! $this->discountService->isCodeRedeemableForPlan($discountCode, $user, $subscription->plan)) {
            return false;
        }

        $discount = $this->discountService->getActiveDiscountByCode($discountCode);

        $paymentProvider = $this->paymentService->getPaymentProviderBySlug(
            $subscription->paymentProvider()->firstOrFail()->slug
        );

        $result = $paymentProvider->addDiscountToSubscription($subscription, $discount);

        if ($result) {
            $this->discountService->redeemCodeForSubscription($discountCode, $user, $subscription->id);

            return true;
        }

        return false;
    }
}
