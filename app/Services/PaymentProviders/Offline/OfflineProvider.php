<?php

namespace App\Services\PaymentProviders\Offline;

use App\Constants\PaymentProviderConstants;
use App\Constants\PlanType;
use App\Constants\SubscriptionType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\OrderService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\SubscriptionService;
use Exception;

class OfflineProvider implements PaymentProviderInterface
{
    public function __construct(
        private OrderService $orderService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function initSubscriptionCheckout(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): array
    {
        $paymentProvider = $this->assertProviderIsActive();

        $this->subscriptionService->updateSubscription(
            $subscription,
            [
                'type' => SubscriptionType::LOCALLY_MANAGED,
                'payment_provider_id' => $paymentProvider->id,
            ]
        );

        return [];
    }

    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        bool $withProration = false
    ): bool {
        throw new Exception('It is not possible to change plan for an offline payment provider');
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        return true;
    }

    public function discardSubscriptionCancellation(Subscription $subscription): bool
    {
        return true;
    }

    public function getChangePaymentMethodLink(Subscription $subscription): string
    {
        throw new Exception('Offline payment provider does not support changing payment method');
    }

    public function initProductCheckout(Order $order, ?Discount $discount = null): array
    {
        $paymentProvider = $this->assertProviderIsActive();

        $this->orderService->updateOrder(
            $order,
            [
                'is_local' => true,
                'payment_provider_id' => $paymentProvider->id,
            ]
        );

        return [];
    }

    public function createProductCheckoutRedirectLink(Order $order, ?Discount $discount = null): string
    {
        throw new Exception('Not a redirect payment provider');
    }

    public function getSlug(): string
    {
        return PaymentProviderConstants::OFFLINE_SLUG;
    }

    public function createSubscriptionCheckoutRedirectLink(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): string
    {
        throw new Exception('Not a redirect payment provider');
    }

    public function isRedirectProvider(): bool
    {
        return false;
    }

    public function isOverlayProvider(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return PaymentProvider::where('slug', $this->getSlug())->firstOrFail()->name;
    }

    private function assertProviderIsActive(): PaymentProvider
    {
        $paymentProvider = PaymentProvider::where('slug', $this->getSlug())->firstOrFail();

        if ($paymentProvider->is_active === false) {
            throw new Exception('Payment provider is not active: '.$this->getSlug());
        }

        return $paymentProvider;
    }

    public function addDiscountToSubscription(Subscription $subscription, Discount $discount): bool
    {
        return true;
    }

    public function supportsPlan(Plan $plan): bool
    {
        return $plan->type === PlanType::FLAT_RATE->value;
    }

    public function reportUsage(Subscription $subscription, int $unitCount): bool
    {
        throw new Exception('Offline payent does not support usage based billing');
    }

    public function supportsSkippingTrial(): bool
    {
        return false;
    }

    public function supportsSeatBasedWithIncludedSeats(): bool
    {
        return false;
    }

    public function updateSubscriptionQuantity(Subscription $subscription, int $quantity, bool $isProrated = true): bool
    {
        $this->subscriptionService->updateSubscription(
            $subscription,
            [
                'quantity' => $quantity,
            ]
        );

        return true;
    }

    public function supportsOneTimePurchaseProductQuantity(): bool
    {
        return true;
    }

    public function supportsSetupFees(): bool
    {
        return true;
    }
}
