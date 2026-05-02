<?php

namespace App\Filament\Admin\Widgets;

use App\Services\CurrencyService;
use App\Services\MetricsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MetricsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        /** @var CurrencyService $currencyService */
        $currencyService = resolve(CurrencyService::class);
        $currency = $currencyService->getMetricsCurrency();

        /** @var MetricsService $metricsService */
        $metricsService = resolve(MetricsService::class);

        $currentMrr = $metricsService->calculateMRR(now());
        $previewMrr = $metricsService->calculateMRR(Carbon::yesterday());
        $mrrDescription = '';
        $mrrIcon = '';
        $color = 'gray';

        if ($previewMrr) {
            $mrrDescription = $previewMrr == $currentMrr ? '' : ($previewMrr > $currentMrr ? __('decrease') : __('increase'));

            if (strlen($mrrDescription) > 0) {
                $mrrDescription = money(abs($currentMrr - $previewMrr), $currency->code).' '.$mrrDescription;
                $mrrIcon = $previewMrr > $currentMrr ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up';
                $color = $previewMrr > $currentMrr ? 'danger' : 'success';
            }
        }

        return [
            Stat::make(
                __('MRR'),
                money($currentMrr, $currency->code)
            )->description($mrrDescription)
                ->descriptionIcon($mrrIcon)
                ->color($color)
                ->chart([7, 2, 10, 3, 15, 4, 17])  // just for decoration :)
            ,
            Stat::make(
                __('Active Subscriptions'),
                $metricsService->getActiveSubscriptions()
            ),
            Stat::make(
                __('Total revenue'),
                $metricsService->getTotalRevenue()
            ),
            Stat::make(
                __('Total user subscription conversion'),
                $metricsService->getTotalCustomerConversion()
            )->description(__('subscribed / total users')),
            Stat::make(
                __('Total Transactions'),
                $metricsService->getTotalTransactions()
            ),

            Stat::make(
                __('Total Users'),
                $metricsService->getTotalUsers()
            ),
        ];
    }
}
