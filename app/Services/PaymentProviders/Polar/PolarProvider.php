<?php

namespace App\Services\PaymentProviders\Polar;

use App\Client\PolarClient;
use App\Constants\DiscountConstants;
use App\Constants\PaymentProviderConstants;
use App\Constants\PlanMeterConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SubscriptionType;
use App\Models\Discount;
use App\Models\OneTimeProduct;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
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

class PolarProvider implements PaymentProviderInterface
{
    public function __construct(
        private PolarClient $client,
        private SubscriptionService $subscriptionService,
        private CalculationService $calculationService,
        private PlanService $planService,
        private OneTimeProductService $oneTimeProductService,
        private DiscountService $discountService,
    ) {}

    public function getSlug(): string
    {
        return PaymentProviderConstants::POLAR_SLUG;
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

        $productId = $this->findOrCreateSubscriptionProduct($plan, $paymentProvider, $quantity);

        $params = [
            'allow_discount_codes' => false,
            'products' => [$productId],
            'customer_email' => $user->email,
            'success_url' => $this->getSubscriptionCheckoutSuccessUrl($subscription),
            'metadata' => [
                'subscription_uuid' => $subscription->uuid,
            ],
        ];

        if ($plan->type === PlanType::SEAT_BASED->value) {
            $params['seats'] = $quantity;
        }

        if ($plan->has_trial && $this->subscriptionService->shouldSkipTrial($subscription)) {
            $params['allow_trial'] = false;
        }

        if ($discount) {
            $currency = $subscription->currency()->firstOrFail();
            $discountId = $this->findOrCreatePolarDiscount($discount, $paymentProvider, $currency->code);
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
            'allow_discount_codes' => false,
            'products' => [$productId],
            'customer_email' => $user->email,
            'success_url' => route('checkout.product.success'),
            'metadata' => [
                'order_uuid' => $order->uuid,
            ],
        ];

        if ($discount) {
            $currency = $order->currency()->firstOrFail();
            $discountId = $this->findOrCreatePolarDiscount($discount, $paymentProvider, $currency->code);
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
            $productId = $this->findOrCreateSubscriptionProduct($newPlan, $paymentProvider, $subscription->quantity);

            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'product_id' => $productId,
                    'proration_behavior' => $withProration ? 'invoice' : 'prorate',
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
        $paymentProvider = $this->assertProviderIsActive();

        $plan = $subscription->plan()->firstOrFail();
        $this->findOrCreateSubscriptionProduct($plan, $paymentProvider, $subscription->quantity);

        $currency = $subscription->currency()->firstOrFail();
        $discountId = $this->findOrCreatePolarDiscount($discount, $paymentProvider, $currency->code);

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

    public function supportsPlan(Plan $plan): bool
    {
        if (in_array($plan->type, [
            PlanType::FLAT_RATE->value,
            PlanType::SEAT_BASED->value,
        ])
        ) {
            return true;
        }

        if ($plan->type == PlanType::USAGE_BASED->value) {
            $planPrice = $this->calculationService->getPlanPrice($plan);

            return $planPrice->type === PlanPriceType::USAGE_BASED_PER_UNIT->value;
        }

        return false;
    }

    public function reportUsage(Subscription $subscription, int $unitCount): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        $plan = $subscription->plan()->firstOrFail();
        $meter = $plan->meter()->first();

        if (! $meter) {
            Log::error('Polar reportUsage called for plan without a meter: '.$plan->id);

            return false;
        }

        try {
            $this->findOrCreatePolarMeter($plan, $paymentProvider);
        } catch (Exception $e) {
            Log::error('Failed to ensure Polar meter exists: '.$e->getMessage());

            return false;
        }

        $paymentProviderMeter = $this->planService->getPaymentProviderMeter($meter, $paymentProvider);

        $eventName = $paymentProviderMeter->data[PlanMeterConstants::POLAR_METER_EVENT_NAME] ?? null;

        if (! $eventName) {
            Log::error('Polar event name not found for meter: '.$meter->name);

            return false;
        }

        $customerId = $subscription->extra_payment_provider_data['customer_id'] ?? null;

        if (! $customerId) {
            Log::error('Polar customer ID not found for subscription: '.$subscription->id);

            return false;
        }

        try {
            $response = $this->client->ingestEvents([
                'events' => [
                    [
                        'name' => $eventName,
                        'customer_id' => $customerId,
                        'metadata' => [
                            'value' => $unitCount,
                        ],
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Failed to ingest Polar usage event: '.$response->body());

                return false;
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

    public function supportsOneTimePurchaseProductQuantity(): bool
    {
        return false;
    }

    public function supportsSetupFees(): bool
    {
        return false;
    }

    private function findOrCreateSubscriptionProduct(Plan $plan, PaymentProvider $paymentProvider, int $quantity = 1): string
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
            'prices' => $this->buildPolarProductPrices($plan, $planPrice, $currencyCode, $paymentProvider, $quantity),
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

    private function buildPolarProductPrices(
        Plan $plan,
        PlanPrice $planPrice,
        string $currencyCode,
        PaymentProvider $paymentProvider,
        int $quantity = 1,
    ): array {
        if ($plan->type === PlanType::USAGE_BASED->value) {
            $meterId = $this->findOrCreatePolarMeter($plan, $paymentProvider);

            $prices = [];

            if ($planPrice->price > 0) {
                $prices[] = [
                    'amount_type' => 'fixed',
                    'price_amount' => $planPrice->price,
                    'price_currency' => $currencyCode,
                ];
            }

            $prices[] = [
                'amount_type' => 'metered_unit',
                'price_currency' => $currencyCode,
                'unit_amount' => $planPrice->price_per_unit,
                'meter_id' => $meterId,
            ];

            return $prices;
        }

        if ($plan->type === PlanType::SEAT_BASED->value) {
            return [
                [
                    'amount_type' => 'seat_based',
                    'price_currency' => $currencyCode,
                    'seat_tiers' => [
                        'seat_tier_type' => 'volume',
                        'tiers' => [
                            [
                                'min_seats' => $quantity,
                                'max_seats' => null,
                                'price_per_seat' => $planPrice->price,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            [
                'amount_type' => 'fixed',
                'price_amount' => $planPrice->price,
                'price_currency' => $currencyCode,
            ],
        ];
    }

    private function findOrCreatePolarMeter(Plan $plan, PaymentProvider $paymentProvider): string
    {
        $meter = $plan->meter()->firstOrFail();

        $existingMeterId = $this->planService->getPaymentProviderMeterId($meter, $paymentProvider);

        if ($existingMeterId !== null) {
            return $existingMeterId;
        }

        $eventName = Str::slug($meter->name).'-'.Str::lower(Str::random(6));

        $response = $this->client->createMeter([
            'name' => $meter->name,
            'filter' => [
                'conjunction' => 'and',
                'clauses' => [
                    [
                        'property' => 'name',
                        'operator' => 'eq',
                        'value' => $eventName,
                    ],
                ],
            ],
            'aggregation' => [
                'func' => 'sum',
                'property' => 'value',
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Failed to create Polar meter: '.$response->body());
            throw new Exception('Failed to create Polar meter');
        }

        $polarMeterId = $response->json()['id'] ?? null;

        if ($polarMeterId === null) {
            Log::error('Failed to get Polar meter ID from response: '.$response->body());
            throw new Exception('Failed to create Polar meter');
        }

        $this->planService->addPaymentProviderMeterId($meter, $paymentProvider, $polarMeterId, [
            PlanMeterConstants::POLAR_METER_EVENT_NAME => $eventName,
        ]);

        return $polarMeterId;
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

    private function findOrCreatePolarDiscount(Discount $discount, PaymentProvider $paymentProvider, string $currency): string
    {
        $existingDiscountId = $this->discountService->getPaymentProviderDiscountId($discount, $paymentProvider);

        if ($existingDiscountId !== null) {
            return $existingDiscountId;
        }

        $discountCode = $discount->codes()->first()->code ?? '';
        $discountCode = preg_replace('/[^A-Za-z0-9]/', '', $discountCode);
        $code = strtoupper($discountCode.Str::random(16));

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
        ];

        if ($discount->type === DiscountConstants::TYPE_FIXED) {
            $params['amount'] = intval($discount->amount);
            $params['currency'] = strtolower($currency);
        } else {
            // Polar expects basis points for percentage discounts (1 basis point = 0.01%).
            $params['basis_points'] = intval($discount->amount) * 100;
        }

        if ($duration === 'repeating' && $discount->duration_in_months !== null) {
            $params['duration_in_months'] = intval($discount->duration_in_months);
        }

        if ($discount->valid_until !== null) {
            $params['ends_at'] = Carbon::parse($discount->valid_until)->toIso8601String();
        }

        $response = $this->client->createDiscount($params);

        if (! $response->successful()) {
            Log::error('Failed to create Polar discount: '.$response->body());
            throw new Exception('Failed to create Polar discount');
        }

        $polarDiscountId = $response->json()['id'] ?? null;

        if ($polarDiscountId === null) {
            Log::error('Failed to get Polar discount ID from response: '.$response->body());
            throw new Exception('Failed to create Polar discount');
        }

        $this->discountService->addPaymentProviderDiscountId($discount, $paymentProvider, $polarDiscountId);

        return $polarDiscountId;
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
            $response = $this->client->updateSubscription(
                $subscription->payment_provider_subscription_id,
                [
                    'seats' => $quantity,
                    'proration_behavior' => $isProrated ? 'invoice' : 'prorate',
                ],
            );

            if (! $response->successful()) {
                throw new Exception('Failed to update Polar subscription seats: '.$response->body());
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
