<?php

namespace App\Filament\Admin\Resources\EmailProviders\Pages;

use App\Filament\Admin\Resources\EmailProviders\EmailProviderResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailProviders extends ListRecords
{
    protected static string $resource = EmailProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
