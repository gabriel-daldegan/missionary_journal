<?php

namespace App\Services;

use App\Constants\PaymentProviderPlanPriceType;
use App\Constants\PlanType;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanMeter;
use App\Models\PlanMeterPaymentProviderData;
use App\Models\PlanPaymentProviderData;
use App\Models\PlanPrice;
use App\Models\PlanPricePaymentProviderData;
use App\Models\Product;
use Illuminate\Support\Collection;

class PlanService
{
    public function __construct(private CurrencyService $currencyService) {}

    public function getPaymentProviderProductId(Plan $plan, PaymentProvider $paymentProvider): ?string
    {
        $result = PlanPaymentProviderData::where('plan_id', $plan->id)
            ->where('payment_provider_id', $paymentProvider->id)
            ->first();

        if ($result) {
            return $result->payment_provider_product_id;
        }

        return null;
    }

    public function findByPaymentProviderProductId(PaymentProvider $paymentProvider, string $paymentProviderProductId): ?Plan
    {
        $result = PlanPaymentProviderData::where('payment_provider_id', $paymentProvider->id)
            ->where('payment_provider_product_id', $paymentProviderProductId)
            ->first();

        if ($result) {
            return Plan::find($result->plan_id);
        }

        return null;
    }

    public function addPaymentProviderProductId(Plan $plan, PaymentProvider $paymentProvider, string $paymentProviderProductId): void
    {
        PlanPaymentProviderData::create([
            'plan_id' => $plan->id,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_product_id' => $paymentProviderProductId,
        ]);
    }

    public function getPaymentProviderPrices(PlanPrice $planPrice, PaymentProvider $paymentProvider): Collection
    {
        return PlanPricePaymentProviderData::where('plan_price_id', $planPrice->id)
            ->where('payment_provider_id', $paymentProvider->id)
            ->get();
    }

    public function getPaymentProviderPriceId(PlanPrice $planPrice, PaymentProvider $paymentProvider, ?PaymentProviderPlanPriceType $type = null): ?string
    {
        $query = PlanPricePaymentProviderData::where('plan_price_id', $planPrice->id)
            ->where('payment_provider_id', $paymentProvider->id);

        if ($type !== null) {
            $query->where('type', $type->value);
        }

        $result = $query->first();

        if ($result) {
            return $result->payment_provider_price_id;
        }

        return null;
    }

    public function getPaymentProviderMeterId(PlanMeter $planMeter, PaymentProvider $paymentProvider): ?string
    {
        $result = $this->getPaymentProviderMeter($planMeter, $paymentProvider);

        if ($result) {
            return $result->payment_provider_plan_meter_id;
        }

        return null;
    }

    public function getPaymentProviderMeter(PlanMeter $planMeter, PaymentProvider $paymentProvider): ?PlanMeterPaymentProviderData
    {
        return PlanMeterPaymentProviderData::where('plan_meter_id', $planMeter->id)
            ->where('payment_provider_id', $paymentProvider->id)
            ->first();
    }

    public function addPaymentProviderMeterId(
        PlanMeter $planMeter,
        PaymentProvider $paymentProvider,
        string $paymentProviderMeterId,
        array $data = [],
    ): void {
        PlanMeterPaymentProviderData::create([
            'plan_meter_id' => $planMeter->id,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_plan_meter_id' => $paymentProviderMeterId,
            'data' => $data,
        ]);
    }

    public function addPaymentProviderPriceId(
        PlanPrice $planPrice,
        PaymentProvider $paymentProvider,
        string $paymentProviderPriceId,
        PaymentProviderPlanPriceType $paymentProviderPlanPriceType = PaymentProviderPlanPriceType::MAIN_PRICE
    ): void {
        PlanPricePaymentProviderData::create([
            'plan_price_id' => $planPrice->id,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_price_id' => $paymentProviderPriceId,
            'type' => $paymentProviderPlanPriceType->value,
        ]);
    }

    public function getActivePlanBySlug(string $slug): ?Plan
    {
        return Plan::where('slug', $slug)->where('is_active', true)->first();
    }

    public function getActivePlanById(int $id): ?Plan
    {
        return Plan::where('id', $id)->where('is_active', true)->first();
    }

    public function getAllActivePlans(?string $planType = null)
    {
        if ($planType) {
            return Plan::where('is_active', true)->where('type', $planType)->get();
        }

        return Plan::where('is_active', true)->get();
    }

    public function getDefaultProduct(): ?Product
    {
        return Product::where('is_default', true)->first();
    }

    public function getAllPlansWithPrices(array $productSlugs = [], ?string $planType = null, bool $onlyVisible = false): Collection
    {
        $currencyObject = $this->currencyService->getCurrency();

        if (count($productSlugs) > 0) {
            // only the plans that have current currency prices
            $result = Plan::where('is_active', true)
                ->with(['product' => function ($query) use ($productSlugs) {
                    $query->whereIn('slug', $productSlugs);
                }])
                ->whereHas('product', function ($query) use ($productSlugs) {
                    $query->whereIn('slug', $productSlugs);
                })
                ->whereHas('prices', function ($query) use ($currencyObject) {
                    $query->where('currency_id', $currencyObject->id);
                })
                ->with(['prices' => function ($query) use ($currencyObject) {
                    $query->where('currency_id', $currencyObject->id);
                }]);

            if ($planType) {
                $result->where('type', $planType);
            }

            if ($onlyVisible) {
                $result->where('is_visible', true);
            }

            $result->with([
                'interval',
                'product',
                'prices',
                'prices.currency',
            ]);

            return $result->get();

        }

        // only the plans that have current currency prices
        $result = Plan::where('is_active', true)
            ->whereHas('prices', function ($query) use ($currencyObject) {
                $query->where('currency_id', $currencyObject->id);
            })
            ->with(['prices' => function ($query) use ($currencyObject) {
                $query->where('currency_id', $currencyObject->id);
            }]);

        if ($planType) {
            $result->where('type', $planType);
        }

        if ($onlyVisible) {
            $result->where('is_visible', true);
        }

        $result->with([
            'interval',
            'product',
            'prices',
            'prices.currency',
        ]);

        return $result->get();
    }

    public function getPlanPrice(Plan $plan): ?PlanPrice
    {
        $currency = $this->currencyService->getCurrency();

        foreach ($plan->prices as $price) {
            if ($price->currency_id === $currency->id) {
                return $price;
            }
        }

        return null;
    }

    public function isPlanChangeable(Plan $plan)
    {
        if ($plan->type === PlanType::USAGE_BASED->value) {
            // usage based plans are not upgradable because users pay at the end of the billing cycle, and they can abuse the system
            // by using a lot of resources and then downgrading to a lower plan, and do that infinitely without paying
            return false;
        }

        return true;
    }
}
