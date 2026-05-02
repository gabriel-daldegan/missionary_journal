<?php

namespace App\Services\PaymentProviders\LemonSqueezy;

use App\Client\LemonSqueezyClient;
use App\Constants\DiscountConstants;
use App\Constants\LemonSqueezyConstants;
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
use App\Services\DiscountService;
use App\Services\OneTimeProductService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LemonSqueezyProvider implements PaymentProviderInterface
{
    public function __construct(
        private LemonSqueezyClient $client,
        private SubscriptionService $subscriptionService,
        private CalculationService $calculationService,
        private PlanService $planService,
        private DiscountService $discountService,
        private OneTimeProductService $oneTimeProductService,
    ) {}

    public function createSubscriptionCheckoutRedirectLink(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        /** @var User $user */
        $user = auth()->user();

        $variantId = $this->planService->getPaymentProviderProductId($plan, $paymentProvider);

        if ($variantId === null) {
            Log::error('Failed to find variant ID for plan: (did you forget to add it to the plan?) '.$plan->id);
            throw new Exception('Failed to find variant ID for plan');
        }

        $price = $this->calculationService->getPlanPrice($plan);

        $object = [
            'custom_price' => $price->price,
            'product_options' => [
                'description' => $plan->description ?? $plan->name,
                'redirect_url' => $this->getSubscriptionCheckoutSuccessUrl($subscription),
                'enabled_variants' => [
                    $variantId,
                ],
            ],
            'checkout_options' => [
                'discount' => false,
            ],
            'checkout_data' => [
                'email' => $user->email,
                'name' => $user->name,
                'custom' => [
                    'subscription_uuid' => $subscription->uuid,
                ],
                'variant_quantities' => [
                    [
                        'variant_id' => intval($variantId),
                        'quantity' => $quantity,
                    ],
                ],
            ],
        ];

        $shouldSkipTrial = $this->subscriptionService->shouldSkipTrial($subscription);

        if ($shouldSkipTrial) {
            $object['checkout_options']['skip_trial'] = true;
        }

        if ($discount) {
            // discounts should not crash the checkout even if they fail to create
            try {
                $object['checkout_data']['discount_code'] = $this->findOrCreateLemonSqueezyDiscount($discount, $paymentProvider);
            } catch (Exception $e) {
                Log::error('Failed to create lemon-squeezy discount: '.$e->getMessage());
            }
        }

        $response = $this->client->createCheckout($object, $variantId);

        if (! $response->successful()) {
            Log::error('Failed to create lemon-squeezy checkout: '.$response->body());
            throw new Exception('Failed to create lemon-squeezy checkout');
        }

        $redirectLink = $response->json()['data']['attributes']['url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create lemon-squeezy checkout: '.$response->body());
            throw new Exception('Failed to create lemon-squeezy checkout');
        }

        return $redirectLink;
    }

    public function initProductCheckout(Order $order, ?Discount $discount = null): array
    {
        // lemon squeezy does not need any initialization

        return [];
    }

    public function createProductCheckoutRedirectLink(Order $order, ?Discount $discount = null): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        $variantIds = [];
        $variantQuantities = [];
        /** @var User $user */
        $user = auth()->user();

        $variantId = null;

        $customPrice = 0;
        foreach ($order->items()->get() as $item) {
            $product = $item->oneTimeProduct()->firstOrFail();
            $variantId = $this->oneTimeProductService->getPaymentProviderProductId($product, $paymentProvider);

            if ($variantId === null) {
                Log::error('Failed to find variant ID for product: (did you forget to add it to the product?) '.$product->id);
                throw new Exception('Failed to find variant ID for product');
            }

            $variantQuantities[] = [
                'variant_id' => intval($variantId),
                'quantity' => $item->quantity,
            ];

            $variantIds[] = $variantId;

            $customPrice = $item->price_per_unit;
        }

        $object = [
            'custom_price' => $customPrice,
            'product_options' => [
                'redirect_url' => route('checkout.product.success'),
                'enabled_variants' => $variantIds,
            ],
            'checkout_options' => [
                'discount' => false,
            ],
            'checkout_data' => [
                'email' => $user->email,
                'name' => $user->name,
                'custom' => [
                    'order_uuid' => $order->uuid,
                ],
                'variant_quantities' => $variantQuantities,
            ],
        ];

        if ($discount) {
            // discounts should not crash the checkout even if they fail to create
            try {
                $object['checkout_data']['discount_code'] = $this->findOrCreateLemonSqueezyDiscount($discount, $paymentProvider);
            } catch (Exception $e) {
                Log::error('Failed to create lemon-squeezy discount: '.$e->getMessage());
            }
        }

        if ($variantId === null) {
            Log::error('Failed to find variant ID for product: (did you forget to add it to the product?) '.$product->id);
            throw new Exception('Failed to find variant ID for product');
        }

        $response = $this->client->createCheckout($object, $variantId);

        if (! $response->successful()) {
            Log::error('Failed to create lemon-squeezy checkout: '.$response->body());
            throw new Exception('Failed to create lemon-squeezy checkout');
        }

        $redirectLink = $response->json()['data']['attributes']['url'] ?? null;

        if ($redirectLink === null) {
            Log::error('Failed to create lemon-squeezy checkout: '.$response->body());
            throw new Exception('Failed to create lemon-squeezy checkout');
        }

        return $redirectLink;
    }

    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        bool $withProration = false
    ): bool {
        $paymentProvider = $this->assertProviderIsActive();

        try {

            $variantId = $this->planService->getPaymentProviderProductId($newPlan, $paymentProvider);

            if ($variantId === null) {
                Log::error('Failed to find variant ID for plan while changing subscription plan: (did you forget to add it to the plan?) '.$newPlan->id);
                throw new Exception('Failed to find variant ID for plan while changing subscription plan');
            }

            $planPrice = $this->calculationService->getPlanPrice($newPlan);

            $response = $this->client->updateSubscription($subscription->payment_provider_subscription_id, $variantId, $withProration);

            if (! $response->successful()) {
                throw new Exception('Failed to update lemon-squeezy subscription');
            }

            $subscription = $this->subscriptionService->updateSubscription($subscription, [
                'plan_id' => $newPlan->id,
                'price' => $planPrice->price,
                'currency_id' => $planPrice->currency_id,
                'interval_id' => $newPlan->interval_id,
                'interval_count' => $newPlan->interval_count,
            ]);

            if ($subscription->plan->type === PlanType::SEAT_BASED->value) {
                // unfortunately, lemon-squeezy resets the quantity to 1 when changing the plan, so we need to update it again
                $this->updateSubscriptionQuantity($subscription, $subscription->quantity, $withProration);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());

            throw $e;
        }

        return true;
    }

    public function updateSubscriptionQuantity(Subscription $subscription, int $quantity, bool $isProrated = true): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $lemonSqueezySubscription = $this->client->getSubscription($subscription->payment_provider_subscription_id)->json('data');
            $subscriptionItemId = $lemonSqueezySubscription['attributes']['first_subscription_item']['id'] ?? null;

            if ($subscriptionItemId === null) {
                throw new Exception('Failed to get lemon-squeezy subscription item ID');
            }

            $response = $this->client->updateSubscriptionQuantity($subscriptionItemId, $quantity, $isProrated);

            if (! $response->successful()) {
                throw new Exception('Failed to update lemon-squeezy subscription quantity');
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $response = $this->client->cancelSubscription($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to cancel lemon-squeezy subscription');
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function discardSubscriptionCancellation(Subscription $subscription): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $response = $this->client->discardSubscriptionCancellation($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to discard lemon-squeezy subscription cancellation');
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function getChangePaymentMethodLink(Subscription $subscription): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $response = $this->client->getSubscription($subscription->payment_provider_subscription_id);

            if (! $response->successful()) {
                throw new Exception('Failed to get lemon-squeezy subscription');
            }

            $url = $response->json()['data']['attributes']['urls']['update_payment_method'] ?? '/';

            return $url;

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return '/';
        }
    }

    public function addDiscountToSubscription(Subscription $subscription, Discount $discount): bool
    {
        throw new Exception('It is not possible to add a discount to a lemon-squeezy subscription');
    }

    public function getSlug(): string
    {
        return PaymentProviderConstants::LEMON_SQUEEZY_SLUG;
    }

    public function initSubscriptionCheckout(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): array
    {
        // lemon squeezy does not need any initialization

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

    private function findOrCreateLemonSqueezyDiscount(Discount $discount, PaymentProvider $paymentProvider): string
    {
        $discountId = $this->discountService->getPaymentProviderDiscountId($discount, $paymentProvider);

        if ($discountId !== null) {
            return $discountId;
        }

        $discountCode = $discount->codes()->first()->code ?? null;
        $discountCode = $discountCode ?? '';
        // remove any non-alphanumeric characters
        $discountCode = preg_replace('/[^A-Za-z0-9]/', '', $discountCode);

        $code = $discountCode.Str::random(16);

        $code = strtoupper($code);

        $duration = 'once';
        $durationInMonths = null;

        if ($discount->duration_in_months !== null) {
            $duration = 'repeating';
            $durationInMonths = (int) $discount->duration_in_months;
        } elseif ($discount->is_recurring) {
            $duration = 'forever';
        }

        $response = $this->client->createDiscount(
            $discount->name,
            $code,
            intval($discount->amount),
            $discount->type === DiscountConstants::TYPE_FIXED ? 'fixed' : 'percent',
            $discount->max_redemptions > 0 ? $discount->max_redemptions : null,
            $duration,
            $durationInMonths,
            $discount->valid_until !== null ? Carbon::parse($discount->valid_until) : null,
        );

        if (! $response->successful()) {
            Log::error('Failed to create lemon-squeezy discount: '.$response->body());
            throw new Exception('Failed to create lemon-squeezy discount');
        }

        $this->discountService->addPaymentProviderDiscountId($discount, $paymentProvider, $code);

        return $code;
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

    public function supportsPlan(Plan $plan): bool
    {
        return in_array($plan->type, [
            PlanType::FLAT_RATE->value,
            PlanType::USAGE_BASED->value,
            PlanType::SEAT_BASED->value,
        ]);
    }

    public function reportUsage(Subscription $subscription, int $unitCount): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        try {
            $subscriptionItemId = $subscription->extra_payment_provider_data[LemonSqueezyConstants::SUBSCRIPTION_ITEM_ID] ?? null;

            if ($subscriptionItemId === null) {
                Log::error('Failed to find subscription item ID for subscription: '.$subscription->id);
                throw new Exception('Failed to find subscription item ID for subscription');
            }

            $response = $this->client->reportUsage($subscriptionItemId, $unitCount);

            if (! $response->successful()) {
                Log::error('Failed to report usage to lemon-squeezy for subscription: '.$subscription->id, $response->json());
                throw new Exception('Failed to report usage to lemon-squeezy for subscription: '.$subscription->id);
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }

    public function supportsSkippingTrial(): bool
    {
        return true;
    }

    public function supportsSeatBasedWithIncludedSeats(): bool
    {
        return false;
    }

    public function supportsOneTimePurchaseProductQuantity(): bool
    {
        return true;
    }

    public function supportsSetupFees(): bool
    {
        return true;
    }

    private function getSubscriptionCheckoutSuccessUrl(Subscription $subscription)
    {
        if ($subscription->type === SubscriptionType::LOCALLY_MANAGED) {
            return route('checkout.convert-local-subscription.success');
        }

        return route('checkout.subscription.success');
    }
}
