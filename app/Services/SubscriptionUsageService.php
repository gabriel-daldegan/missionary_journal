<?php

namespace App\Services;

use App\Constants\PlanType;
use App\Constants\SubscriptionType;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Services\PaymentProviders\PaymentService;
use Filament\Facades\Filament;

class SubscriptionUsageService
{
    public function __construct(
        private PaymentService $paymentService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function reportUsage(int $unitCount, ?Subscription $subscription = null): bool
    {
        $subscription = $subscription ?? $this->subscriptionService->findActiveTenantSubscriptionWithPlanType(PlanType::USAGE_BASED, Filament::getTenant());

        if (! $subscription) {
            return false;
        }

        if ($subscription->plan->type !== PlanType::USAGE_BASED->value) {
            return false;
        }

        $result = true;
        if ($subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED) {
            $paymentProvider = $this->paymentService->getPaymentProviderBySlug(
                $subscription->paymentProvider->slug
            );

            $result = $paymentProvider->reportUsage($subscription, $unitCount);
        }

        if ($result) {
            SubscriptionUsage::create([
                'subscription_id' => $subscription->id,
                'unit_count' => $unitCount,
            ]);
        }

        return $result;
    }
}
