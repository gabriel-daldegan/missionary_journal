<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                DatePicker::make('start_date')
                    ->default(now()->subYear()->toDateString())
                    ->afterStateHydrated(function (DatePicker $component, ?string $state) {
                        if (! $state) {
                            $component->state(now()->subYear()->toDateString());
                        }
                    })
                    ->label(__('Start Date')),
                DatePicker::make('end_date')
                    ->default(date(now()->toDateString()))
                    ->afterStateHydrated(function (DatePicker $component, ?string $state) {
                        if (! $state) {
                            $component->state(now()->toDateString());
                        }
                    })
                    ->label(__('End Date')),
                Select::make('period')->label(__('Period'))->options([
                    'day' => __('Day'),
                    'week' => __('Week'),
                    'month' => __('Month'),
                    'year' => __('Year'),
                ])->default('month'),

            ])->columnSpanFull()
                ->columns(3),
        ]);
    }
}
