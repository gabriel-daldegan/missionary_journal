<?php

namespace App\Filament\Admin\Widgets;

use App\Services\MetricsService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class ChurnChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

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

        $data = $metricsService->calculateSubscriptionChurnRateChart($period, $startDate, $endDate);

        return [
            'datasets' => [
                [
                    'label' => 'Churn rate',
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
        return __('Churn rate overview');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('Churn rate is the % of users who cancel their subscription each month.');
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            scales: {
                y: {
                    ticks: {
                        callback: (value) => value + '%',
                    },
                },
            },
        }
    JS);
    }
}
