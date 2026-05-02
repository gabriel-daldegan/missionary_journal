<?php

namespace App\Filament\Dashboard\Resources\Teams\Schemas;

use App\Services\TenantPermissionService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->helperText(__('The name of the team.'))
                    ->required(),
                Select::make('roles')
                    ->multiple()
                    ->label(__('Roles'))
                    ->relationship('roles', 'name')
                    ->options(function (TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getAllAvailableTenantRolesForDisplay(Filament::getTenant(), true);
                    })
                    ->helperText(__('Choose the role for this team. All permissions associated with this role will be granted to team members.'))
                    ->preload(),
            ]);
    }
}
