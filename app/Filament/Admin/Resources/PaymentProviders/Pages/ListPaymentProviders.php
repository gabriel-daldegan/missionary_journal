<?php

namespace App\Filament\Admin\Resources\PaymentProviders\Pages;

use App\Filament\Admin\Resources\PaymentProviders\PaymentProviderResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentProviders extends ListRecords
{
    protected static string $resource = PaymentProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
