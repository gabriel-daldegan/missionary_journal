<?php

namespace App\Filament\Admin\Resources\Transactions\Widgets;

use App\Constants\TransactionStatus;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransactionOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var CurrencyService $currencyService */
        $currencyService = resolve(CurrencyService::class);
        $currency = $currencyService->getCurrency();

        return [
            Stat::make(
                __('Total'),
                money(Transaction::where('status', TransactionStatus::SUCCESS->value)->sum('amount'), $currency->code)
            ),
            Stat::make(
                __('Total fees'),
                money(Transaction::where('status', TransactionStatus::SUCCESS->value)->sum('total_fees'), $currency->code)
            ),
            Stat::make(
                __('Transaction count'),
                Transaction::count()
            ),
        ];
    }
}
