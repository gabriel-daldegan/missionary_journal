<?php

namespace App\Filament\Dashboard\Resources\Roles;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Roles\Pages\CreateRole;
use App\Filament\Dashboard\Resources\Roles\Pages\EditRole;
use App\Filament\Dashboard\Resources\Roles\Pages\ListRoles;
use App\Models\Permission;
use App\Models\Role;
use App\Services\TenantPermissionService;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static bool $isScopedToTenant = false;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getNavigationGroup(): ?string
    {
        return __('Team Management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->required()
                        ->label(__('Name'))
                        ->helperText(__('The name of the role.'))
                        ->maxLength(255),
                    Select::make('permissions')
                        ->relationship('permissions', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('name', 'like', TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX.'%'))
                        ->getOptionLabelFromRecordUsing(function (Model $record) {
                            return Str($record->name)->replace(TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX, '')->title();
                        })
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                $failedPermissions = [];
                                Permission::whereIn('id', $value)->get()->each(function ($permission) use (&$failedPermissions) {
                                    if (! str_starts_with($permission->name, TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX)) {
                                        $failedPermissions[] = $permission->name;
                                    }
                                });

                                if (count($failedPermissions) > 0) {
                                    $fail(__('The following permissions are not allowed for tenancy roles -> :permissions', [
                                        'prefix' => TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX,
                                        'permissions' => implode(', ', $failedPermissions),
                                    ]));
                                }
                            },
                        ])
                        ->preload()
                        ->multiple()
                        ->label(__('Permissions'))
                        ->helperText(__('Choose the permissions for this role.'))
                        ->placeholder(__('Select permissions...')),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(__('Manage and create custom roles for your team.'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format'))->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Roles');
    }

    public static function canAccess(): bool
    {
        /** @var TenantPermissionService $tenantPermissionService */
        $tenantPermissionService = app(TenantPermissionService::class);

        return config('app.can_add_tenant_specific_roles_from_tenant_dashboard', false) && $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_MANAGE_TEAM,
        );
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', Filament::getTenant()->id);
    }
}
