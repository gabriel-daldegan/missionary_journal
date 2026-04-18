<?php

namespace App\Services\PaymentProviders\Creem;

use App\Client\CreemClient;
use App\Constants\DiscountConstants;
use App\Constants\PaymentProviderConstants;
use App\Constants\PlanType;
use App\Constants\SubscriptionType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CalculationService;
use App\Services\OneTimeProductService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreemProvider implements PaymentProviderInterface
{
    public function __construct(
        private CreemClient $client,
        private SubscriptionService $subscriptionService,
        private CalculationService $calculationService,
        private PlanService $planService,
        private OneTimeProductService $oneTimeProductService,
    ) {}

    public function getSlug(): string
    {
        return PaymentProviderConstants::CREEM_SLUG;
    }

    public function getName(): string
    {
        return PaymentProvider::where('slug', $this->getSlug())->firstOrFail()->name;
    }

    public function createSubscriptionCheckoutRedirectLink(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        /** @var User $user */
        $user = auth()->user();

        $productId = $this->planService->getPaymentProviderProductId($plan, $paymentProvider);

        if ($productId === null) {
            Log::error('Failed to find Creem product ID for plan: (did you forget to add it to the plan?) '.$plan->id);
            throw new Exception('Failed to find Creem product ID for plan');
        }

        $params = [
            'product_id' => $productId,
            'units' => $quantity,
            'success_url' => $this->getSubscriptionCheckoutSuccessUrl($subscription),
            'customer' => [
                'email' => $user->email,
            ],
            'metadata' => [
                'subscription_uuid' => $subscription->uuid,
            ],
        ];

        if ($discount) {
            try {
                $discountCode = $this->createCreemDiscount($discount, $productId);
                $params['discount_code'] = $discountCode;
            } catch (Exception $e) {
                Log::error('Failed to create Creem discount: '.$e->getMessage());
            }
        }

        $response = $this->client->createCheckout($params);

        if (! $response->successful()) {
            Log::error('Failed to create Creem checkout: '.$response->body());
            throw new Exception('Failed to create Creem checkout');
        }

        $redirectLink = $response->json()['checkout_url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create Creem checkout: '.$response->body());
            throw new Exception('Failed to create Creem checkout');
        }

        return $redirectLink;
    }

    public function createProductCheckoutRedirectLink(Order $order, ?Discount $discount = null): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        /** @var User $user */
        $user = auth()->user();

        $firstItem = $order->items()->first();

        if ($firstItem === null) {
            throw new Exception('Order has no items');
        }

        $product = $firstItem->oneTimeProduct()->firstOrFail();
        $productId = $this->oneTimeProductService->getPaymentProviderProductId($product, $paymentProvider);

        if ($productId === null) {
            Log::error('Failed to find Creem product ID for product: (did you forget to add it to the product?) '.$product->id);
            throw new Exception('Failed to find Creem product ID for product');
        }

        $params = [
            'product_id' => $productId,
            'success_url' => route('checkout.product.success'),
            'customer' => [
                'email' => $user->email,
            ],
            'metadata' => [
                'order_uuid' => $order->uuid,
            ],
        ];

        if ($discount) {
            try {
                $discountCode = $this->createCreemDiscount($discount, $productId);
                $params['discount_code'] = $discountCode;
            } catch (Exception $e) {
                Log::error('Failed to create Creem discount: '.$e->getMessage());
            }
        }

        $response = $this->client->createCheckout($params);

        if (! $response->successful()) {
            Log::error('Failed to create Creem checkout: '.$response->body());
            throw new Exception('Failed to create Creem checkout');
        }

        $redirectLink = $response->json()['checkout_url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create Creem checkout: '.$response->body());
            throw new Exception('Failed to create Creem checkout');
        }

        return $redirectLink;
    }

    public function initSubscriptionCheckout(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): array
    {
        return [];
    }

    public function initProductCheckout(Order $order, ?Discount $discount = null): array
    {
        return [];
    }

    public function isRedirectProvider(): bool
    {
        return true;
    }

    public function isOverlayProvider(): bool
    {
        return false;
    }

    public function changePlan(Subscription $subscription, Plan $newPlan, bool $withProration = false): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $productId = $this->planService->getPaymentProviderProductId($newPlan, $paymentProvider);

            if ($productId === null) {
                Log::error('Failed to find Creem product ID for plan while changing subscription plan: (did you forget to add it to the plan?) '.$newPlan->id);
                throw new Exception('Failed to find Creem product ID for plan while changing subscription plan');
            }

            $updateBehavior = $withProration ? 'proration-charge-immediately' : 'proration-none';

            $response = $this->client->upgradeSubscription(
                $subscription->payment_provider_subscription_id,
                $productId,
                $updateBehavior,
            );

            if (! $response->successful()) {
                throw new Exception('Failed to upgrade Creem subscription');
            }

            $planPrice = $this->calculationService->getPlanPrice($newPlan);

            $this->subscriptionService->updateSubscription($subscription, [
                'plan_id' => $newPlan->id,
                'price' => $planPrice->price,
                'currency_id' => $planPrice->currency_id,
                'interval_id' => $newPlan->interval_id,
                'interval_count' => $newPlan->interval_count,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            throw $e;
        }

        return true;
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        $this->assertProviderIsActive();

        try {
            $response = $this->client->cancelSubscription($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to cancel Creem subscription');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function discardSubscriptionCancellation(Subscription $subscription): bool
    {
        $this->assertProviderIsActive();

        try {
            $response = $this->client->resumeSubscription($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to resume Creem subscription');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function getChangePaymentMethodLink(Subscription $subscription): string
    {
        $this->assertProviderIsActive();

        try {
            $customerId = $subscription->extra_payment_provider_data['customer_id'] ?? null;

            if ($customerId === null) {
                Log::error('Failed to find Creem customer ID for subscription: '.$subscription->id);

                return '/';
            }

            $response = $this->client->getCustomerBillingPortal($customerId);

            if (! $response->successful()) {
                throw new Exception('Failed to get Creem billing portal link');
            }

            return $response->json()['customer_portal_link'] ?? '/';
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return '/';
        }
    }

    public function addDiscountToSubscription(Subscription $subscription, Discount $discount): bool
    {
        throw new Exception('It is not possible to add a discount to an existing Creem subscription');
    }

    public function supportsPlan(Plan $plan): bool
    {
        return in_array($plan->type, [
            PlanType::FLAT_RATE->value,
            PlanType::SEAT_BASED->value,
        ]);
    }

    public function reportUsage(Subscription $subscription, int $unitCount): bool
    {
        return false;
    }

    public function supportsSkippingTrial(): bool
    {
        return false;
    }

    public function supportsOneTimePurchaseProductQuantity(): bool
    {
        return false;
    }

    public function supportsSetupFees(): bool
    {
        return false;
    }

    private function createCreemDiscount(Discount $discount, string $productId): string
    {
        $sessionKey = "creem_discount_{$discount->id}_{$productId}";
        $cachedCode = session($sessionKey);

        if ($cachedCode !== null) {
            return $cachedCode;
        }

        $code = strtoupper(Str::random(14));

        $duration = 'once';
        if ($discount->duration_in_months !== null) {
            $duration = 'repeating';
        } elseif ($discount->is_recurring) {
            $duration = 'forever';
        }

        $params = [
            'name' => $discount->name,
            'code' => $code,
            'type' => $discount->type === DiscountConstants::TYPE_FIXED ? 'fixed' : 'percentage',
            'duration' => $duration,
            'applies_to_products' => [$productId],
        ];

        if ($discount->duration_in_months !== null) {
            $params['duration_in_months'] = $discount->duration_in_months;
        }

        if ($discount->type === DiscountConstants::TYPE_FIXED) {
            $params['amount'] = intval($discount->amount);
        } else {
            $params['percentage'] = intval($discount->amount);
        }

        $response = $this->client->createDiscount($params);

        if (! $response->successful()) {
            Log::error('Failed to create Creem discount: '.$response->body());
            throw new Exception('Failed to create Creem discount');
        }

        session([$sessionKey => $code]);

        return $code;
    }

    private function assertProviderIsActive(): PaymentProvider
    {
        $paymentProvider = PaymentProvider::where('slug', $this->getSlug())->firstOrFail();

        if ($paymentProvider->is_active === false) {
            throw new Exception('Payment provider is not active: '.$this->getSlug());
        }

        return $paymentProvider;
    }

    private function getSubscriptionCheckoutSuccessUrl(Subscription $subscription): string
    {
        if ($subscription->type === SubscriptionType::LOCALLY_MANAGED) {
            return route('checkout.convert-local-subscription.success');
        }

        return route('checkout.subscription.success');
    }

    public function updateSubscriptionQuantity(Subscription $subscription, int $quantity, bool $isProrated = true): bool
    {
        $this->assertProviderIsActive();

        try {
            $response = $this->client->getSubscription($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to get Creem subscription');
            }

            $subscriptionData = $response->json();
            $items = $subscriptionData['items'] ?? [];

            if (empty($items)) {
                throw new Exception('No items found on Creem subscription');
            }

            $updatedItems = [];
            foreach ($items as $item) {
                $updatedItems[] = [
                    'id' => $item['id'],
                    'price_id' => $item['price_id'],
                    'product_id' => $item['product_id'],
                    'units' => $quantity,
                ];
            }

            $updateBehavior = $isProrated ? 'proration-charge-immediately' : 'proration-none';

            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                $updatedItems,
                $updateBehavior,
            );

            if (! $response->successful()) {
                throw new Exception('Failed to update Creem subscription quantity');
            }

            $this->subscriptionService->updateSubscription($subscription, [
                'quantity' => $quantity,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function supportsSeatBasedWithIncludedSeats(): bool
    {
        return false;
    }
}
