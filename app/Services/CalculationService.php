<?php

namespace App\Services;

use App\Constants\DiscountConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Dto\CartDto;
use App\Dto\SubscriptionTotalsDto;
use App\Dto\TotalsDto;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Exception;

class CalculationService
{
    public function __construct(
        private PlanService $planService,
        private DiscountService $discountService,
        private OneTimeProductService $oneTimeProductService,
        private CurrencyService $currencyService,
    ) {}

    /**
     * Subscription price equals to the plan price
     */
    public function getPlanPrice(Plan $plan): PlanPrice
    {
        $currency = $this->currencyService->getCurrency();

        $planPrice = $plan->prices()->where('currency_id', $currency->id)->firstOrFail();

        return $planPrice;
    }

    public function getOneTimeProductPrice(OneTimeProduct $oneTimeProduct): OneTimeProductPrice
    {
        $currency = $this->currencyService->getCurrency();

        return $oneTimeProduct->prices()->where('currency_id', $currency->id)->firstOrFail();
    }

    public function calculatePlanTotals(?User $user, string $planSlug, ?string $discountCode = null, ?int $quantity = 1, string $actionType = DiscountConstants::ACTION_TYPE_ANY): TotalsDto
    {
        $plan = $this->planService->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            throw new Exception('Plan not found');
        }

        if ($discountCode !== null && ! $this->discountService->isCodeRedeemableForPlan($discountCode, $user, $plan, $actionType)) {
            throw new Exception('Discount code is not redeemable');
        }

        $planPrice = $this->getPlanPrice($plan);
        $currencyCode = $planPrice->currency->code;
        $totalsDto = new TotalsDto;

        $totalsDto->currencyCode = $currencyCode;

        $totalsDto->setupFee = $planPrice->setup_fee ?? 0;
        if ($plan->type === PlanType::SEAT_BASED->value) {
            if ($planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                $extraSeats = max(0, $quantity - $planPrice->included_seats);
                $totalsDto->subtotal = $planPrice->price + ($extraSeats * $planPrice->extra_seat_price);
                $totalsDto->basePrice = $planPrice->price;
                $totalsDto->includedSeats = $planPrice->included_seats;
                $totalsDto->extraSeatPrice = $planPrice->extra_seat_price;
                $totalsDto->extraSeats = $extraSeats;
            } else {
                $totalsDto->subtotal = $planPrice->price * $quantity;
            }
        } else {
            $totalsDto->subtotal = $planPrice->price;
        }

        $totalsDto->discountAmount = 0;
        if ($discountCode !== null) {
            $totalsDto->discountAmount = $this->discountService->getDiscountAmount($discountCode, $totalsDto->subtotal);
        }

        $totalsDto->amountDue = max(0, $totalsDto->subtotal - $totalsDto->discountAmount) + $totalsDto->setupFee;

        $totalsDto->planPriceType = $planPrice->type;
        $totalsDto->pricePerUnit = $planPrice->price_per_unit;
        $totalsDto->tiers = $planPrice->tiers;

        return $totalsDto;
    }

    public function calculateNewPlanTotals(Subscription $subscription, string $planSlug, bool $withProration = false): TotalsDto
    {
        $newPlan = $this->planService->getActivePlanBySlug($planSlug);

        if ($newPlan === null) {
            throw new Exception('Plan not found');
        }

        $planPrice = $this->getPlanPrice($newPlan);
        $currencyCode = $planPrice->currency->code;
        $totalsDto = new SubscriptionTotalsDto;

        $totalsDto->currencyCode = $currencyCode;

        $totalsDto->discountAmount = 0;

        if ($newPlan->type === PlanType::SEAT_BASED->value) {
            $quantity = $subscription->tenant->users->count();
            if ($planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                $extraSeats = max(0, $quantity - $planPrice->included_seats);
                $totalsDto->subtotal = $planPrice->price + ($extraSeats * $planPrice->extra_seat_price);
                $totalsDto->basePrice = $planPrice->price;
                $totalsDto->includedSeats = $planPrice->included_seats;
                $totalsDto->extraSeatPrice = $planPrice->extra_seat_price;
                $totalsDto->extraSeats = $extraSeats;
            } else {
                $totalsDto->subtotal = $planPrice->price * $quantity;
                $totalsDto->pricePerSeat = $planPrice->price;
            }
            $totalsDto->quantity = $quantity;
        } else {
            $totalsDto->subtotal = $planPrice->price;
        }

        if (! $withProration) {
            $totalsDto->amountDue = max(0, $totalsDto->subtotal - $totalsDto->discountAmount);
        }

        return $totalsDto;
    }

    public function calculateCartTotals(CartDto $cart, ?User $user): TotalsDto
    {
        $totalsDto = new TotalsDto;
        $totalsDto->currencyCode = $this->currencyService->getCurrency()->code;
        $currency = Currency::where('code', $totalsDto->currencyCode)->firstOrFail();

        $totalAmount = 0;
        $totalAmountAfterDiscount = 0;

        foreach ($cart->items as $item) {

            $product = $this->oneTimeProductService->getOneTimeProductById($item->productId);
            $productPrice = $product->prices()->where('currency_id', $currency->id)->firstOrFail();

            $totalAmount += $productPrice->price * $item->quantity;

            $itemDiscountedPrice = $productPrice->price;
            $discountCode = $cart->discountCode;
            if ($discountCode !== null && $this->discountService->isCodeRedeemableForOneTimeProduct($discountCode, $user, $product)) {
                $discountAmount = $this->discountService->getDiscountAmount($discountCode, $productPrice->price);
                $itemDiscountedPrice = max(0, $productPrice->price - $discountAmount);
            }

            $totalAmountAfterDiscount += $itemDiscountedPrice * $item->quantity;
        }

        $totalsDto->subtotal = $totalAmount;
        $totalsDto->amountDue = $totalAmountAfterDiscount;
        $totalsDto->discountAmount = max(0, $totalAmount - $totalAmountAfterDiscount);

        return $totalsDto;
    }

    public function calculateOrderTotals(Order $order, User $user, ?string $discountCode = null)
    {
        $currency = $this->currencyService->getCurrency();

        $totalAmount = 0;
        $totalAmountAfterDiscount = 0;

        $orderItems = $order->items()->get();

        foreach ($orderItems as $orderItem) {

            $product = $orderItem->oneTimeProduct()->firstOrFail();
            $productPrice = $product->prices()->where('currency_id', $currency->id)->firstOrFail();

            $orderItem->price_per_unit = $productPrice->price;

            $totalAmount += $orderItem->price_per_unit * $orderItem->quantity;

            $itemDiscountedPrice = $orderItem->price_per_unit;
            if ($discountCode !== null && $this->discountService->isCodeRedeemableForOneTimeProduct($discountCode, $user, $product)) {
                $discountAmount = $this->discountService->getDiscountAmount($discountCode, $orderItem->price_per_unit);
                $itemDiscountedPrice = max(0, $orderItem->price_per_unit - $discountAmount);
            }

            $orderItem->price_per_unit_after_discount = $itemDiscountedPrice;
            $orderItem->discount_per_unit = max(0, $orderItem->price_per_unit - $itemDiscountedPrice);

            $totalAmountAfterDiscount += $itemDiscountedPrice * $orderItem->quantity;
            $orderItem->currency_id = $currency->id;

            $orderItem->save();
        }

        $order->total_amount = $totalAmount;
        $order->total_amount_after_discount = $totalAmountAfterDiscount;
        $order->total_discount_amount = max(0, $totalAmount - $totalAmountAfterDiscount);
        $order->currency_id = $currency->id;

        $order->save();
    }
}
