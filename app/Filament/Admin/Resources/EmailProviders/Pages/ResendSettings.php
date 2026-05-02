<?php

namespace App\Filament\Admin\Resources\EmailProviders\Pages;

use App\Filament\Admin\Resources\EmailProviders\EmailProviderResource;
use App\Services\ConfigService;
use Filament\Resources\Pages\Page;

class ResendSettings extends Page
{
    protected static string $resource = EmailProviderResource::class;

    protected string $view = 'filament.admin.resources.email-provider-resource.pages.resend-settings';

    public function mount(): void
    {
        static::authorizeResourceAccess();
    }

    public static function canAccess(array $parameters = []): bool
    {
        $configService = app()->make(ConfigService::class);

        return $configService->isAdminSettingsEnabled();
    }
}
