<?php

namespace App\Filament\Admin\Resources\EmailProviders\Pages;

use App\Filament\Admin\Resources\EmailProviders\EmailProviderResource;
use Filament\Resources\Pages\Page;

class AmazonSesSettings extends Page
{
    protected static string $resource = EmailProviderResource::class;

    protected string $view = 'filament.admin.resources.email-provider-resource.pages.amazon-ses-settings';

    public function mount(): void
    {
        static::authorizeResourceAccess();
    }
}
