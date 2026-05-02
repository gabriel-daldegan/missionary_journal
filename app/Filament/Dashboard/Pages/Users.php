<?php

namespace App\Filament\Dashboard\Pages;

use App\Constants\TenancyPermissionConstants;
use App\Services\TenantPermissionService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Users extends Page
{
    protected string $view = 'filament.dashboard.pages.users';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedUsers;

    public static function getNavigationGroup(): ?string
    {
        return __('Team Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function canAccess(): bool
    {
        $tenantPermissionService = app(TenantPermissionService::class); // a bit ugly, but this is the Filament way :/

        return config('app.allow_tenant_invitations', false) && $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_MANAGE_TEAM
        );
    }
}
