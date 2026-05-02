<?php

namespace App\Filament\Admin\Resources\PaymentProviders\Pages;

use App\Filament\Admin\Resources\PaymentProviders\PaymentProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentProvider extends CreateRecord
{
    protected static string $resource = PaymentProviderResource::class;
}
