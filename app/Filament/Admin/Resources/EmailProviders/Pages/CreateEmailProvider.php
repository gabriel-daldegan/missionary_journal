<?php

namespace App\Filament\Admin\Resources\EmailProviders\Pages;

use App\Filament\Admin\Resources\EmailProviders\EmailProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailProvider extends CreateRecord
{
    protected static string $resource = EmailProviderResource::class;
}
