<?php

namespace App\Filament\Dashboard\Resources\Teams;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Teams\Pages\CreateTeam;
use App\Filament\Dashboard\Resources\Teams\Pages\EditTeam;
use App\Filament\Dashboard\Resources\Teams\Pages\ListTeams;
use App\Filament\Dashboard\Resources\Teams\RelationManagers\TenantUsersRelationManager;
use App\Filament\Dashboard\Resources\Teams\Schemas\TeamForm;
use App\Filament\Dashboard\Resources\Teams\Tables\TeamsTable;
use App\Models\Team;
use App\Services\TenantPermissionService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TenantUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var TenantPermissionService $tenantPermissionService */
        $tenantPermissionService = app(TenantPermissionService::class); // a bit ugly, but this is the Filament way :/

        return config('app.teams_enabled', false) && $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_MANAGE_TEAM,
        );
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Team Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Teams');
    }
}
