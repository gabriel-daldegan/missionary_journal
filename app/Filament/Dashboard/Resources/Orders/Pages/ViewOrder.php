<?php

namespace App\Filament\Dashboard\Resources\Orders\Pages;

use App\Filament\Dashboard\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
