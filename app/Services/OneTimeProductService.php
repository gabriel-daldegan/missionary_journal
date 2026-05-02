<?php

namespace App\Services;

use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPaymentProviderData;
use App\Models\OneTimeProductPrice;
use App\Models\OneTimeProductPricePaymentProviderData;
use App\Models\PaymentProvider;
use Illuminate\Support\Collection;

class OneTimeProductService
{
    public function __construct(
        private CurrencyService $currencyService,
    ) {}

    public function getOneTimeProductById(?int $id): OneTimeProduct
    {
        return OneTimeProduct::findOrFail($id);
    }

    public function getActiveOneTimeProductById(?int $id): OneTimeProduct
    {
        return OneTimeProduct::where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function findByPaymentProviderProductId(PaymentProvider $paymentProvider, string $paymentProviderProductId): ?OneTimeProduct
    {
        $result = OneTimeProductPaymentProviderData::where('payment_provider_id', $paymentProvider->id)
            ->where('payment_provider_product_id', $paymentProviderProductId)
            ->first();

        if ($result) {
            return OneTimeProduct::find($result->one_time_product_id);
        }

        return null;
    }

    public function getProductPrice(OneTimeProduct $product): ?OneTimeProductPrice
    {
        $currency = $this->currencyService->getCurrency();

        foreach ($product->prices as $price) {
            if ($price->currency_id === $currency->id) {
                return $price;
            }
        }

        return null;
    }

    public function getProductWithPriceBySlug(string $slug): ?OneTimeProduct
    {
        $currencyObject = $this->currencyService->getCurrency();

        return OneTimeProduct::where('slug', $slug)
            ->where('is_active', true)
            ->whereHas('prices', function ($query) use ($currencyObject) {
                $query->where('currency_id', $currencyObject->id);
            })
            ->with(['prices' => function ($query) use ($currencyObject) {
                $query->where('currency_id', $currencyObject->id);
            }])
            ->first();
    }

    public function getAllProductsWithPrices(string $sortBy = 'name', string $sortDirection = 'asc', bool $onlyVisible = false): Collection
    {
        $sortBy = in_array($sortBy, ['id', 'price', 'name', 'created_at', 'updated_at']) ? $sortBy : 'id';
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';

        $defaultCurrencyObject = $this->currencyService->getCurrency();

        $query = OneTimeProduct::where('is_active', true)
            ->whereHas('prices', function ($query) use ($defaultCurrencyObject) {
                $query->where('currency_id', $defaultCurrencyObject->id);
            });

        if ($sortBy === 'price') {
            $query->join('one_time_product_prices', 'one_time_products.id', '=', 'one_time_product_prices.one_time_product_id')
                ->where('one_time_product_prices.currency_id', $defaultCurrencyObject->id)
                ->orderBy('one_time_product_prices.price', $sortDirection)
                ->select('one_time_products.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        if ($onlyVisible) {
            $query->where('is_visible', true);
        }

        $query->with([
            'prices',
            'prices.currency',
        ]);

        return $query->get();
    }

    public function getPaymentProviderProductId(OneTimeProduct $oneTimeProduct, PaymentProvider $paymentProvider): ?string
    {
        $result = OneTimeProductPaymentProviderData::where('one_time_product_id', $oneTimeProduct->id)
            ->where('payment_provider_id', $paymentProvider->id)
            ->first();

        if ($result) {
            return $result->payment_provider_product_id;
        }

        return null;
    }

    public function addPaymentProviderProductId(OneTimeProduct $oneTimeProduct, PaymentProvider $paymentProvider, string $paymentProviderProductId): void
    {
        OneTimeProductPaymentProviderData::create([
            'one_time_product_id' => $oneTimeProduct->id,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_product_id' => $paymentProviderProductId,
        ]);
    }

    public function getPaymentProviderPriceId(OneTimeProductPrice $oneTimeProductPrice, PaymentProvider $paymentProvider): ?string
    {
        $result = OneTimeProductPricePaymentProviderData::where('one_time_product_price_id', $oneTimeProductPrice->id)
            ->where('payment_provider_id', $paymentProvider->id)
            ->first();

        if ($result) {
            return $result->payment_provider_price_id;
        }

        return null;
    }

    public function addPaymentProviderPriceId(OneTimeProductPrice $oneTimeProductPrice, PaymentProvider $paymentProvider, string $paymentProviderPriceId): void
    {
        OneTimeProductPricePaymentProviderData::create([
            'one_time_product_price_id' => $oneTimeProductPrice->id,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_price_id' => $paymentProviderPriceId,
        ]);
    }
}
