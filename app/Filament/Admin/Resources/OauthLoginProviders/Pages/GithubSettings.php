<?php

namespace App\Filament\Admin\Resources\OauthLoginProviders\Pages;

use App\Filament\Admin\Resources\OauthLoginProviders\OauthLoginProviderResource;
use App\Services\ConfigService;
use Filament\Resources\Pages\Page;

class GithubSettings extends Page
{
    protected static string $resource = OauthLoginProviderResource::class;

    protected string $view = 'filament.admin.resources.oauth-login-provider-resource.pages.github-settings';

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
