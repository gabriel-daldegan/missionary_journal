<?php

namespace App\Filament\Admin\Resources\Tenants;

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Tenants\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Tenancy');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tenants');
    }

    public static function getPluralLabel(): string
    {
        return __('Tenants');
    }

    public static function getModelLabel(): string
    {
        return __('Tenant');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label(__('Name'))
                    ->maxLength(255),
                Select::make('created_by')
                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(20)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->label(__('Created By'))
                    ->default(auth()->id())
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label(__('Subscriptions'))
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label(__('Orders'))
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('Users'))
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label(__('UUID'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            SubscriptionsRelationManager::class,
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
