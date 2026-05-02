<?php

namespace App\Filament\Admin\Resources\Discounts\Pages;

use App\Filament\Admin\Resources\Discounts\DiscountResource;
use App\Filament\ListDefaults;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscounts extends ListRecords
{
    use ListDefaults;

    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
