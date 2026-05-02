<?php

namespace App\Filament\Admin\Resources\VerificationProviders\Pages;

use App\Filament\Admin\Resources\VerificationProviders\VerificationProviderResource;
use App\Services\ConfigService;
use Filament\Resources\Pages\Page;

class TwilioSettings extends Page
{
    protected static string $resource = VerificationProviderResource::class;

    protected string $view = 'filament.admin.resources.verification-provider-resource.pages.twilio-settings';

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
