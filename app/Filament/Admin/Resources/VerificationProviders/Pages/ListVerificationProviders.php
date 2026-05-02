<?php

namespace App\Filament\Admin\Resources\VerificationProviders\Pages;

use App\Filament\Admin\Resources\VerificationProviders\VerificationProviderResource;
use Filament\Resources\Pages\ListRecords;

class ListVerificationProviders extends ListRecords
{
    protected static string $resource = VerificationProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
