<?php

namespace App\Filament\Admin\Widgets;

use App\Services\CurrencyService;
use App\Services\MetricsService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class TotalRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = $this->pageFilters['start_date'];
        $endDate = $this->pageFilters['end_date'];
        $period = $this->pageFilters['period'];

        // parse the dates to Carbon instances
        $startDate = $startDate ? Carbon::parse($startDate) : null;
        $endDate = $endDate ? Carbon::parse($endDate) : null;

        $metricsService = resolve(MetricsService::class);

        $data = $metricsService->calculateDailyRevenueChart($period, $startDate, $endDate);

        return [

            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => array_values($data),

                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('Total Revenue overview');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('Total Revenue is the total revenue generated.');
    }

    protected function getOptions(): RawJs
    {
        /** @var CurrencyService $currencyService */
        $currencyService = resolve(CurrencyService::class);
        $currency = $currencyService->getMetricsCurrency();
        $symbol = $currency->symbol;

        return RawJs::make(<<<JS
        {
            scales: {
                y: {
                    ticks: {
                        callback: (value) => '$symbol' + value.toFixed(2),
                    },
                },
            },
        }
    JS);
    }
}
