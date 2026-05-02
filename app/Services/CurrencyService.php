<?php

namespace App\Services;

use App\Models\Currency;
use Exception;
use Illuminate\Support\Collection;

class CurrencyService
{
    private static ?Currency $currency = null;

    public function getCurrency(): Currency
    {
        if (static::$currency !== null) {
            return static::$currency;
        }

        // if you have any other logic to determine the currency (for a multi-currency app), you can implement it here
        $defaultCurrency = config('app.default_currency');

        $currency = Currency::where('code', $defaultCurrency)->first();

        if (! $currency) {
            throw new Exception("Default currency not found: {$defaultCurrency}");
        }

        return static::$currency = $currency;
    }

    public function getMetricsCurrency(): Currency
    {
        // This method can be used to get the currency for metrics aggregation
        // In case of a multi-currency setup, you might convert all amounts to a single currency for aggregation purposes if you prefer

        return $this->getCurrency();
    }

    public function getAllCurrencies(string $sortBy = 'name', string $sortDirection = 'asc'): Collection
    {
        return Currency::all()->sortBy($sortBy, SORT_NATURAL, $sortDirection === 'desc');
    }
}
