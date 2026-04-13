<?php

namespace App\Services\PaymentProviders\Polar;

use App\Client\PolarClient;
use App\Constants\PaymentProviderConstants;
use App\Constants\PlanType;
use App\Constants\SubscriptionType;
use App\Models\Discount;
use App\Models\OneTimeProduct;
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

class PolarProvider implements PaymentProviderInterface
{
    public function __construct(
        private PolarClient $client,
        private SubscriptionService $subscriptionService,
        private CalculationService $calculationService,
        private PlanService $planService,
        private OneTimeProductService $oneTimeProductService,
    ) {}

    public function getSlug(): string
    {
        return PaymentProviderConstants::POLAR_SLUG;
    }

    public function getName(): string
    {
        return PaymentProvider::where('slug', $this->getSlug())->firstOrFail()->name;
    }

    public function createSubscriptionCheckoutRedirectLink(Plan $plan, Subscription $subscription, ?Discount $discount = null): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        /** @var User $user */
        $user = auth()->user();

        $productId = $this->findOrCreateSubscriptionProduct($plan, $paymentProvider);

        $params = [
            'products' => [$productId],
            'customer_email' => $user->email,
            'success_url' => $this->getSubscriptionCheckoutSuccessUrl($subscription),
            'metadata' => [
                'subscription_uuid' => $subscription->uuid,
            ],
        ];

        if ($discount) {
            $discountId = $this->getDiscountPaymentProviderId($discount, $paymentProvider);
            if ($discountId) {
                $params['discount_id'] = $discountId;
            }
        }

        $response = $this->client->createCheckout($params);

        if (! $response->successful()) {
            Log::error('Failed to create Polar checkout: '.$response->body());
            throw new Exception('Failed to create Polar checkout');
        }

        $redirectLink = $response->json()['url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create Polar checkout: '.$response->body());
            throw new Exception('Failed to create Polar checkout');
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
        $productId = $this->findOrCreateOneTimeProduct($product, $paymentProvider);

        $params = [
            'products' => [$productId],
            'customer_email' => $user->email,
            'success_url' => route('checkout.product.success'),
            'metadata' => [
                'order_uuid' => $order->uuid,
            ],
        ];

        if ($discount) {
            $discountId = $this->getDiscountPaymentProviderId($discount, $paymentProvider);
            if ($discountId) {
                $params['discount_id'] = $discountId;
            }
        }

        $response = $this->client->createCheckout($params);

        if (! $response->successful()) {
            Log::error('Failed to create Polar checkout: '.$response->body());
            throw new Exception('Failed to create Polar checkout');
        }

        $redirectLink = $response->json()['url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create Polar checkout: '.$response->body());
            throw new Exception('Failed to create Polar checkout');
        }

        return $redirectLink;
    }

    public function initSubscriptionCheckout(Plan $plan, Subscription $subscription, ?Discount $discount = null): array
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
            $productId = $this->findOrCreateSubscriptionProduct($newPlan, $paymentProvider);

            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'product_id' => $productId,
                ],
            );

            if (! $response->successful()) {
                throw new Exception('Failed to update Polar subscription: '.$response->body());
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
            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'cancel_at_period_end' => true,
                ],
            );

            if (! $response->successful()) {
                throw new Exception('Failed to cancel Polar subscription');
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
            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'cancel_at_period_end' => false,
                ],
            );

            if (! $response->successful()) {
                throw new Exception('Failed to resume Polar subscription');
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
                Log::error('Failed to find Polar customer ID for subscription: '.$subscription->id);

                return '/';
            }

            $response = $this->client->createCustomerSession([
                'customer_id' => $customerId,
            ]);

            if (! $response->successful()) {
                throw new Exception('Failed to create Polar customer session');
            }

            return $response->json()['customer_portal_url'] ?? '/';
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return '/';
        }
    }

    public function addDiscountToSubscription(Subscription $subscription, Discount $discount): bool
    {
        $this->assertProviderIsActive();

        $paymentProvider = PaymentProvider::where('slug', $this->getSlug())->firstOrFail();
        $discountId = $this->getDiscountPaymentProviderId($discount, $paymentProvider);

        if (! $discountId) {
            throw new Exception('Failed to find Polar discount ID');
        }

        try {
            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'discount_id' => $discountId,
                ],
            );

            if (! $response->successful()) {
                throw new Exception('Failed to add discount to Polar subscription');
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function getSupportedPlanTypes(): array
    {
        return [
            PlanType::FLAT_RATE->value,
        ];
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

    private function findOrCreateSubscriptionProduct(Plan $plan, PaymentProvider $paymentProvider): string
    {
        $productId = $this->planService->getPaymentProviderProductId($plan, $paymentProvider);

        if ($productId !== null) {
            return $productId;
        }

        $planPrice = $this->calculationService->getPlanPrice($plan);
        $currencyCode = strtolower($planPrice->currency()->firstOrFail()->code);
        $interval = $plan->interval()->firstOrFail();

        $productParams = [
            'name' => $plan->name,
            'recurring_interval' => $interval->date_identifier,
            'recurring_interval_count' => $plan->interval_count,
            'prices' => [
                [
                    'amount_type' => 'fixed',
                    'price_amount' => $planPrice->price,
                    'price_currency' => $currencyCode,
                ],
            ],
        ];

        if ($plan->has_trial) {
            $trialInterval = $plan->trialInterval()->first();
            if ($trialInterval) {
                $productParams['trial_interval'] = $trialInterval->date_identifier;
                $productParams['trial_interval_count'] = $plan->trial_interval_count;
            }
        }

        $productDescription = strip_tags($plan->description ?? '');
        if (! empty($productDescription)) {
            $productParams['description'] = $productDescription;
        }

        $response = $this->client->createProduct($productParams);

        if (! $response->successful()) {
            Log::error('Failed to create Polar product for plan: '.$response->body());
            throw new Exception('Failed to create Polar product for plan');
        }

        $polarProductId = $response->json()['id'] ?? null;

        if ($polarProductId === null) {
            Log::error('Failed to get Polar product ID from response: '.$response->body());
            throw new Exception('Failed to create Polar product for plan');
        }

        $this->planService->addPaymentProviderProductId($plan, $paymentProvider, $polarProductId);

        return $polarProductId;
    }

    private function findOrCreateOneTimeProduct(OneTimeProduct $product, PaymentProvider $paymentProvider): string
    {
        $productId = $this->oneTimeProductService->getPaymentProviderProductId($product, $paymentProvider);

        if ($productId !== null) {
            return $productId;
        }

        $productPrice = $this->calculationService->getOneTimeProductPrice($product);
        $currencyCode = strtolower($productPrice->currency()->firstOrFail()->code);

        $productParams = [
            'name' => $product->name,
            'recurring_interval' => null,
            'recurring_interval_count' => null,
            'prices' => [
                [
                    'amount_type' => 'fixed',
                    'price_amount' => $productPrice->price,
                    'price_currency' => $currencyCode,
                ],
            ],
        ];

        $productDescription = strip_tags($product->description ?? '');
        if (! empty($productDescription)) {
            $productParams['description'] = $productDescription;
        }

        $response = $this->client->createProduct($productParams);

        if (! $response->successful()) {
            Log::error('Failed to create Polar product for one-time product: '.$response->body());
            throw new Exception('Failed to create Polar product for one-time product');
        }

        $polarProductId = $response->json()['id'] ?? null;

        if ($polarProductId === null) {
            Log::error('Failed to get Polar product ID from response: '.$response->body());
            throw new Exception('Failed to create Polar product for one-time product');
        }

        $this->oneTimeProductService->addPaymentProviderProductId($product, $paymentProvider, $polarProductId);

        return $polarProductId;
    }

    private function getDiscountPaymentProviderId(Discount $discount, PaymentProvider $paymentProvider): ?string
    {
        $discountData = $discount->discountPaymentProviderData()
            ->where('payment_provider_id', $paymentProvider->id)
            ->first();

        return $discountData?->payment_provider_discount_id;
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
}
