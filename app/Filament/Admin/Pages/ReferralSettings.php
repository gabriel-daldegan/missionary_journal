<?php

namespace App\Filament\Admin\Pages;

use App\Services\ConfigService;
use Filament\Pages\Page;

class ReferralSettings extends Page
{
    protected string $view = 'filament.admin.pages.referral-settings';

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Referral Settings');
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public static function canAccess(): bool
    {
        $configService = app()->make(ConfigService::class);

        return $configService->isAdminSettingsEnabled()
            && auth()->user()
            && auth()->user()->hasPermissionTo('update settings');
    }
}
