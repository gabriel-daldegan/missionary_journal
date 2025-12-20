<?php

namespace App\Filament\Admin\Resources\Roles;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Models\Permission;
use App\Models\Role;
use Closure;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Roles');
    }

    public static function getModelLabel(): string
    {
        return __('Role');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->label(__('Role Name'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('The name of the role.'))
                        ->disabled(fn (?Model $record) => $record && $record->name === 'admin' && ! $record->is_tenant_role)
                        ->maxLength(255),
                    Toggle::make('is_tenant_role')
                        ->label(__('Is Tenant Role'))
                        ->helperText(__('This role is used for tenant users. If checked, only tenancy permissions can be assigned to this role.'))
                        ->default(false)
                        ->disabledOn('edit')
                        ->live()
                        ->required(),
                    Select::make('permissions')
                        ->label(__('Permissions'))
                        ->disabled(fn (?Model $record) => $record && $record->name === 'admin' && ! $record->is_tenant_role)
                        ->relationship('permissions', 'name',
                            modifyQueryUsing: fn (Builder $query, Get $get) => $query->when($get('is_tenant_role'), function ($query) {
                                $query->where('name', 'like', TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX.'%');
                            }, function ($query) {
                                $query->where('name', 'not like', TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX.'%');
                            }))
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {

                                if ($get('is_tenant_role')) {
                                    $failedPermissions = [];
                                    Permission::query()->whereIn('id', $value)->get()->each(function ($permission) use (&$failedPermissions) {
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
                                } else {
                                    $failedPermissions = [];
                                    Permission::query()->whereIn('id', $value)->get()->each(function ($permission) use (&$failedPermissions) {
                                        if (str_starts_with($permission->name, TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX)) {
                                            $failedPermissions[] = $permission->name;
                                        }
                                    });

                                    if (count($failedPermissions) > 0) {
                                        $fail(__('The following permissions are not allowed for admin roles -> :permissions', [
                                            'prefix' => TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX,
                                            'permissions' => implode(', ', $failedPermissions),
                                        ]));
                                    }
                                }
                            },
                        ])
                        ->preload()
                        ->multiple()
                        ->helperText(__('Choose the permissions for this role. Tenancy permissions can only be assigned to tenancy roles.'))
                        ->placeholder(__('Select permissions...')),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(__('Manage the roles in your application. Roles that start with "tenancy:" are supposed to be used for multi-tenancy users to control user dashboard capabilities.'))
            ->columns([
                TextColumn::make('name')->sortable()->searchable()->label(__('Name')),
                IconColumn::make('is_tenant_role')
                    ->label(__('Is Tenant Role'))
                    ->sortable()
                    ->boolean(),
                TextColumn::make('tenant_id')
                    ->getStateUsing(fn (Role $role) => $role->tenant?->name ?? __('-'))
                    ->label(__('Tenant'))
                    ->sortable()
                    ->searchable(),
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
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('tenant');
            })
            ->toolbarActions([
                DeleteBulkAction::make(),
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
}
