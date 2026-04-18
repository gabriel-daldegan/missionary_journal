<?php

namespace App\Services\PaymentProviders\Paddle;

use App\Client\PaddleClient;
use App\Constants\DiscountConstants;
use App\Constants\PaddleConstants;
use App\Constants\PaymentProviderConstants;
use App\Constants\PaymentProviderPlanPriceType;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\PaymentProviders\Paddle\PaddleUpdatePaymentDetails;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\CalculationService;
use App\Services\DiscountService;
use App\Services\OneTimeProductService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Exception;

class PaddleProvider implements PaymentProviderInterface
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PaddleClient $paddleClient,
        private PlanService $planService,
        private CalculationService $calculationService,
        private DiscountService $discountService,
        private OneTimeProductService $oneTimeProductService,
    ) {}

    public function initSubscriptionCheckout(Plan $plan, Subscription $subscription, ?Discount $discount = null, int $quantity = 1): array
    {
        $paymentProvider = $this->assertProviderIsActive();

        $paddleProductId = $this->planService->getPaymentProviderProductId($plan, $paymentProvider);

        if ($paddleProductId === null) {
            $paddleProductId = $this->createPaddleProductForPlan($plan, $paymentProvider);
        }

        $currency = $subscription->currency()->firstOrFail();

        $planPrice = $this->calculationService->getPlanPrice($plan);

        $shouldSkipTrial = $this->subscriptionService->shouldSkipTrial($subscription);

        if ($shouldSkipTrial && $plan->has_trial) {
            $paddlePrice = $this->planService->getPaymentProviderPriceId($planPrice, $paymentProvider, PaymentProviderPlanPriceType::NO_TRIAL_PRICE);

            if ($paddlePrice === null) {
                $paddlePrice = $this->createPaddlePriceForPlan($plan, $paddleProductId, $currency, $paymentProvider, $planPrice, true, PaymentProviderPlanPriceType::NO_TRIAL_PRICE);
            }
        } else {
            $paddlePrice = $this->planService->getPaymentProviderPriceId($planPrice, $paymentProvider, PaymentProviderPlanPriceType::MAIN_PRICE);

            if ($paddlePrice === null) {
                $paddlePrice = $this->createPaddlePriceForPlan($plan, $paddleProductId, $currency, $paymentProvider, $planPrice);
            }
        }

        if ($plan->type === PlanType::SEAT_BASED->value && $planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
            $extraSeatPaddlePriceId = $this->findOrCreateExtraSeatPrice($plan, $planPrice, $paddleProductId, $currency, $paymentProvider);

            $extraSeats = max(0, $quantity - $planPrice->included_seats);

            $results = [
                'productDetails' => [
                    [
                        'paddleProductId' => $paddleProductId,
                        'paddlePriceId' => $paddlePrice,
                        'quantity' => 1,
                    ],
                ],
            ];

            if ($extraSeats > 0) {
                $results['productDetails'][] = [
                    'paddleProductId' => $paddleProductId,
                    'paddlePriceId' => $extraSeatPaddlePriceId,
                    'quantity' => $extraSeats,
                ];
            }
        } else {
            $results = [
                'productDetails' => [
                    [
                        'paddleProductId' => $paddleProductId,
                        'paddlePriceId' => $paddlePrice,
                        'quantity' => $quantity,
                    ],
                ],
            ];
        }

        if (($planPrice->setup_fee ?? 0) > 0) {
            $setupFeePriceId = $this->findOrCreateSetupFeePrice($planPrice, $paddleProductId, $currency, $paymentProvider);

            $results['productDetails'][] = [
                'paddleProductId' => $paddleProductId,
                'paddlePriceId' => $setupFeePriceId,
                'quantity' => 1,
            ];
        }

        if ($discount !== null) {
            // discounts should not crash the checkout even if they fail to create
            try {
                $paddleDiscountId = $this->findOrCreatePaddleDiscount($discount, $paymentProvider, $currency->code);
                $results['paddleDiscountId'] = $paddleDiscountId;
            } catch (Exception $e) {
                logger()->error('Failed to create paddle discount: '.$e->getMessage());
            }
        }

        return $results;
    }

    public function changePlan(
        Subscription $subscription,
        Plan $newPlan,
        bool $withProration = false
    ): bool {
        $paymentProvider = $this->assertProviderIsActive();

        $paddleProductId = $this->planService->getPaymentProviderProductId($newPlan, $paymentProvider);

        if ($paddleProductId === null) {
            $paddleProductId = $this->createPaddleProductForPlan($newPlan, $paymentProvider);
        }

        $currency = $subscription->currency()->firstOrFail();
        $planPrice = $this->calculationService->getPlanPrice($newPlan);

        $paddlePrice = $this->planService->getPaymentProviderPriceId($planPrice, $paymentProvider, PaymentProviderPlanPriceType::MAIN_PRICE);

        if ($paddlePrice === null) {
            $paddlePrice = $this->createPaddlePriceForPlan($newPlan, $paddleProductId, $currency, $paymentProvider, $planPrice);
        }

        $isTrialing = $subscription->trial_ends_at !== null && Carbon::parse($subscription->trial_ends_at)->isFuture();

        if ($newPlan->type === PlanType::SEAT_BASED->value && $planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
            $extraSeatPaddlePriceId = $this->findOrCreateExtraSeatPrice($newPlan, $planPrice, $paddleProductId, $currency, $paymentProvider);

            $extraSeats = max(0, $subscription->quantity - $planPrice->included_seats);

            $items = [
                [
                    'price_id' => $paddlePrice,
                    'quantity' => 1,
                ],
            ];

            if ($extraSeats > 0) {
                $items[] = [
                    'price_id' => $extraSeatPaddlePriceId,
                    'quantity' => $extraSeats,
                ];
            }

            $response = $this->paddleClient->updateSubscriptionWithItems(
                $subscription->payment_provider_subscription_id,
                $items,
                $withProration,
                $isTrialing,
            );
        } else {
            $response = $this->paddleClient->updateSubscription(
                $subscription->payment_provider_subscription_id,
                $paddlePrice,
                $withProration,
                $isTrialing,
                quantity: $subscription->quantity,
            );
        }

        if ($response->failed()) {
            throw new Exception('Failed to update paddle subscription: '.$response->body());
        }

        $this->subscriptionService->updateSubscription($subscription, [
            'plan_id' => $newPlan->id,
            'price' => $planPrice->price,
            'currency_id' => $planPrice->currency_id,
            'interval_id' => $newPlan->interval_id,
            'interval_count' => $newPlan->interval_count,
        ]);

        return true;
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        $response = $this->paddleClient->cancelSubscription($subscription->payment_provider_subscription_id);

        if ($response->failed()) {

            logger()->error('Failed to cancel paddle subscription: '.$subscription->payment_provider_subscription_id.' '.$response->body());

            return false;
        }

        return true;
    }

    public function discardSubscriptionCancellation(Subscription $subscription): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        $response = $this->paddleClient->discardSubscriptionCancellation($subscription->payment_provider_subscription_id);

        if ($response->failed()) {
            logger()->error('Failed to discard paddle subscription cancellation: '.$subscription->payment_provider_subscription_id.' '.$response->body());

            return false;
        }

        return true;
    }

    public function getChangePaymentMethodLink(Subscription $subscription): string
    {
        $paymentProvider = $this->assertProviderIsActive();

        $response = $this->paddleClient->getPaymentMethodUpdateTransaction($subscription->payment_provider_subscription_id);

        if ($response->failed()) {
            logger()->error('Failed to get paddle payment method update transaction: '.$subscription->payment_provider_subscription_id.' '.$response->body());

            throw new Exception('Failed to get paddle payment method update transaction');
        }

        $responseBody = $response->json()['data'];
        $txId = $responseBody['id'];
        $url = PaddleUpdatePaymentDetails::getUrl();

        return $url.'?_ptxn='.$txId;
    }

    public function updateSubscriptionQuantity(Subscription $subscription, int $quantity, bool $isProrated = true): bool
    {
        $paymentProvider = $this->assertProviderIsActive();

        $plan = $subscription->plan()->firstOrFail();

        $planPrice = $this->calculationService->getPlanPrice($plan);

        $priceId = $this->planService->getPaymentProviderPriceId($planPrice, $paymentProvider, PaymentProviderPlanPriceType::MAIN_PRICE);

        $isTrialing = $subscription->trial_ends_at !== null && Carbon::parse($subscription->trial_ends_at)->isFuture();

        if ($planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
            $currency = $subscription->currency()->firstOrFail();
            $paddleProductId = $this->planService->getPaymentProviderProductId($plan, $paymentProvider);

            $extraSeatPaddlePriceId = $this->findOrCreateExtraSeatPrice($plan, $planPrice, $paddleProductId, $currency, $paymentProvider);

            $extraSeats = max(0, $quantity - $planPrice->included_seats);

            $items = [
                [
                    'price_id' => $priceId,
                    'quantity' => 1,
                ],
            ];

            if ($extraSeats > 0) {
                $items[] = [
                    'price_id' => $extraSeatPaddlePriceId,
                    'quantity' => $extraSeats,
                ];
            }

            $response = $this->paddleClient->updateSubscriptionWithItems(
                $subscription->payment_provider_subscription_id,
                $items,
                $isProrated,
                $isTrialing,
            );
        } else {
            $response = $this->paddleClient->updateSubscriptionQuantity($subscription->payment_provider_subscription_id, $priceId, $quantity, $isTrialing, $isProrated);
        }

        if ($response->failed()) {
            logger()->error('Failed to update paddle subscription quantity: '.$subscription->payment_provider_subscription_id.' '.$response->body());

            return false;
        }

        return true;
    }

    public function initProductCheckout(Order $order, ?Discount $discount = null): array
    {
        $paymentProvider = $this->assertProviderIsActive();

        $results = [
            'productDetails' => [],
        ];

        $currency = $order->currency()->firstOrFail();

        foreach ($order->items()->get() as $item) {
            $product = $item->oneTimeProduct()->firstOrFail();
            $paddleProductId = $this->oneTimeProductService->getPaymentProviderProductId($product, $paymentProvider);

            if ($paddleProductId === null) {
                $paddleProductId = $this->createPaddleProductForOneTimeProduct($product, $paymentProvider);
            }

            $oneTimeProductPrice = $this->calculationService->getOneTimeProductPrice($product);

            $paddlePrice = $this->oneTimeProductService->getPaymentProviderPriceId($oneTimeProductPrice, $paymentProvider);

            if ($paddlePrice === null) {
                $paddlePrice = $this->createPaddlePriceForOneTimeProduct($product, $paddleProductId, $currency, $paymentProvider, $oneTimeProductPrice);
            }

            $results['productDetails'][] = [
                'paddleProductId' => $paddleProductId,
                'paddlePriceId' => $paddlePrice,
                'quantity' => $item->quantity,
            ];
        }

        if ($discount !== null) {
            // discounts should not crash the checkout even if they fail to create
            try {
                $paddleDiscountId = $this->findOrCreatePaddleDiscount($discount, $paymentProvider, $currency->code);
                $results['paddleDiscountId'] = $paddleDiscountId;
            } catch (Exception $e) {
                logger()->error('Failed to create paddle discount: '.$e->getMessage());
            }
        }

        return $results;
    }

    public function createProductCheckoutRedirectLink(Order $order, ?Discount $discount = null): string
    {
        throw new Exception('Not a redirect payment provider');
    }

    public function getSlug(): string
    {
        return PaymentProviderConstants::PADDLE_SLUG;
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
        return true;
    }

    public function getName(): string
    {
        return PaymentProvider::where('slug', $this->getSlug())->firstOrFail()->name;
    }

    private function findOrCreatePaddleDiscount(Discount $discount, PaymentProvider $paymentProvider, string $currencyCode)
    {
        $paddleDiscountId = $this->discountService->getPaymentProviderDiscountId($discount, $paymentProvider);

        if ($paddleDiscountId !== null) {
            return $paddleDiscountId;
        }

        $amount = strval($discount->amount);

        $description = empty($discount->description) ? $discount->name : $discount->description;
        $discountType = $discount->type === DiscountConstants::TYPE_FIXED ? PaddleConstants::DISCOUNT_TYPE_FLAT : PaddleConstants::DISCOUNT_TYPE_PERCENTAGE;

        $response = $this->paddleClient->createDiscount(
            $amount,
            $description,
            $discountType,
            $currencyCode,
            $discount->is_recurring,
            $discount->maximum_recurring_intervals,
            $discount->valid_until !== null ? Carbon::parse($discount->valid_until) : null,
        );

        if ($response->failed()) {
            throw new Exception('Failed to create paddle discount: '.$response->body());
        }

        $paddleDiscountId = $response->json()['data']['id'];

        $this->discountService->addPaymentProviderDiscountId($discount, $paymentProvider, $paddleDiscountId);

        return $paddleDiscountId;
    }

    private function createPaddleProductForPlan(Plan $plan, PaymentProvider $paymentProvider): mixed
    {
        $createProductResponse = $this->paddleClient->createProduct(
            $plan->name,
            strip_tags($plan->product()->firstOrFail()->description),
            'standard'
        );

        if ($createProductResponse->failed()) {
            throw new Exception('Failed to create paddle product: '.$createProductResponse->body());
        }

        $paddleProductId = $createProductResponse->json()['data']['id'];

        $this->planService->addPaymentProviderProductId($plan, $paymentProvider, $paddleProductId);

        return $paddleProductId;
    }

    private function createPaddleProductForOneTimeProduct(OneTimeProduct $oneTimeProduct, PaymentProvider $paymentProvider): mixed
    {
        $createProductResponse = $this->paddleClient->createProduct(
            $oneTimeProduct->name,
            strip_tags($oneTimeProduct->description ?? $oneTimeProduct->name),
            'standard'
        );

        if ($createProductResponse->failed()) {
            throw new Exception('Failed to create paddle product: '.$createProductResponse->body());
        }

        $paddleProductId = $createProductResponse->json()['data']['id'];

        $this->oneTimeProductService->addPaymentProviderProductId($oneTimeProduct, $paymentProvider, $paddleProductId);

        return $paddleProductId;
    }

    private function createPaddlePriceForPlan(
        Plan $plan,
        string $paddleProductId,
        Currency $currency,
        PaymentProvider $paymentProvider,
        PlanPrice $planPrice,
        bool $skipTrial = false,
        PaymentProviderPlanPriceType $priceType = PaymentProviderPlanPriceType::MAIN_PRICE,
    ) {
        $trialInterval = null;
        $trialFrequency = null;

        if (! $skipTrial && $plan->has_trial) {
            $trialInterval = $plan->trialInterval()->firstOrFail()->date_identifier;
            $trialFrequency = $plan->trial_interval_count;
        }

        $maxQuantity = 1;
        if ($plan->type === PlanType::SEAT_BASED->value && $planPrice->type !== PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
            $maxQuantity = $plan->max_users_per_tenant > 0 ? $plan->max_users_per_tenant : 10000;
        }

        $response = $this->paddleClient->createPriceForPlan(
            $paddleProductId,
            $plan->interval()->firstOrFail()->date_identifier,
            $plan->interval_count,
            $planPrice->price,
            $currency->code,
            $trialInterval,
            $trialFrequency,
            $maxQuantity,
        );

        if ($response->failed()) {
            throw new Exception('Failed to create paddle price: '.$response->body());
        }

        $paddlePrice = $response->json()['data']['id'];

        $this->planService->addPaymentProviderPriceId($planPrice, $paymentProvider, $paddlePrice, $priceType);

        return $paddlePrice;
    }

    private function findOrCreateSetupFeePrice(
        PlanPrice $planPrice,
        string $paddleProductId,
        Currency $currency,
        PaymentProvider $paymentProvider,
    ): string {
        $existingPrices = $this->planService->getPaymentProviderPrices($planPrice, $paymentProvider);

        foreach ($existingPrices as $existingPrice) {
            if ($existingPrice->type === PaymentProviderPlanPriceType::SETUP_FEE_PRICE->value) {
                return $existingPrice->payment_provider_price_id;
            }
        }

        $response = $this->paddleClient->createPriceForOneTimeProduct(
            $paddleProductId,
            $planPrice->setup_fee,
            $currency->code,
            'Setup fee',
            1,
        );

        if ($response->failed()) {
            throw new Exception('Failed to create paddle setup fee price: '.$response->body());
        }

        $paddleSetupFeePrice = $response->json()['data']['id'];

        $this->planService->addPaymentProviderPriceId($planPrice, $paymentProvider, $paddleSetupFeePrice, PaymentProviderPlanPriceType::SETUP_FEE_PRICE);

        return $paddleSetupFeePrice;
    }

    private function createPaddlePriceForOneTimeProduct(
        OneTimeProduct $oneTimeProduct,
        string $paddleProductId,
        Currency $currency,
        PaymentProvider $paymentProvider,
        OneTimeProductPrice $oneTimeProductPrice
    ) {

        $response = $this->paddleClient->createPriceForOneTimeProduct(
            $paddleProductId,
            $oneTimeProductPrice->price,
            $currency->code,
            $oneTimeProduct->name,
            $oneTimeProduct->max_quantity,
        );

        if ($response->failed()) {
            throw new Exception('Failed to create paddle price: '.$response->body());
        }

        $paddlePrice = $response->json()['data']['id'];

        $this->oneTimeProductService->addPaymentProviderPriceId($oneTimeProductPrice, $paymentProvider, $paddlePrice);

        return $paddlePrice;
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
        $paymentProvider = $this->assertProviderIsActive();

        $currency = $subscription->currency()->firstOrFail();

        $paddleDiscountId = $this->findOrCreatePaddleDiscount($discount, $paymentProvider, $currency->code);

        $response = $this->paddleClient->addDiscountToSubscription(
            $subscription->payment_provider_subscription_id,
            $paddleDiscountId,
        );

        if ($response->failed()) {
            logger()->error('Failed to add paddle discount to subscription: '.$subscription->payment_provider_subscription_id.' '.$response->body());

            return false;
        }

        return true;
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
        throw new Exception('Padddle does not support usage based billing');
    }

    public function supportsSkippingTrial(): bool
    {
        return true;
    }

    public function supportsSeatBasedWithIncludedSeats(): bool
    {
        return true;
    }

    private function findOrCreateExtraSeatPrice(
        Plan $plan,
        PlanPrice $planPrice,
        string $paddleProductId,
        Currency $currency,
        PaymentProvider $paymentProvider,
    ): string {
        $existingPrices = $this->planService->getPaymentProviderPrices($planPrice, $paymentProvider);

        foreach ($existingPrices as $existingPrice) {
            if ($existingPrice->type === PaymentProviderPlanPriceType::EXTRA_SEAT_PRICE->value) {
                return $existingPrice->payment_provider_price_id;
            }
        }

        $maxQuantity = $plan->max_users_per_tenant > 0
            ? max(1, $plan->max_users_per_tenant - $planPrice->included_seats)
            : 10000;

        $response = $this->paddleClient->createPriceForPlan(
            $paddleProductId,
            $plan->interval()->firstOrFail()->date_identifier,
            $plan->interval_count,
            $planPrice->extra_seat_price,
            $currency->code,
            maxQuantity: $maxQuantity,
        );

        if ($response->failed()) {
            throw new Exception('Failed to create paddle extra seat price: '.$response->body());
        }

        $paddleExtraSeatPrice = $response->json()['data']['id'];

        $this->planService->addPaymentProviderPriceId($planPrice, $paymentProvider, $paddleExtraSeatPrice, PaymentProviderPlanPriceType::EXTRA_SEAT_PRICE);

        return $paddleExtraSeatPrice;
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
