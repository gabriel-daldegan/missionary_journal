<?php

namespace App\Services;

use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\PaymentProviders\PaymentService;

class TenantSubscriptionService
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function getTenantSubscriptions(Tenant $tenant)
    {
        return $tenant
            ->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->with('plan')
            ->get();
    }

    public function updateSubscriptionQuantity(Subscription $subscription, int $quantity): bool
    {
        if ($subscription->plan->type !== PlanType::SEAT_BASED->value) {
            return true;
        }

        if ($subscription->type === SubscriptionType::LOCALLY_MANAGED) {
            $subscription->quantity = $quantity;
            $subscription->save();

            return true;
        }

        $isProrated = config('app.payment.proration_enabled', true);

        $paymentProvider = $this->paymentService->getPaymentProviderBySlug(
            $subscription->paymentProvider->slug
        );

        return $paymentProvider->updateSubscriptionQuantity($subscription, $quantity, $isProrated);
    }

    public function calculateCurrentSubscriptionQuantity(Subscription $subscription): int
    {
        $plan = $subscription->plan;

        if ($plan->type === PlanType::SEAT_BASED->value) {
            return $subscription->tenant->users()->count();
        }

        return 1;
    }

    public function syncSubscriptionQuantities()
    {
        $subscriptions = Subscription::where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->whereHas('plan', function ($query) {
                $query->where('type', PlanType::SEAT_BASED->value);
            })->cursor();

        foreach ($subscriptions as $subscription) {
            $quantity = $this->calculateCurrentSubscriptionQuantity($subscription);

            if ($subscription->quantity < $quantity) {
                logger()->info('Updating subscription quantity', [
                    'subscription_id' => $subscription->id,
                    'old_quantity' => $subscription->quantity,
                    'new_quantity' => $quantity,
                ]);
                $this->updateSubscriptionQuantity($subscription, $quantity);
            }
        }

    }
}
