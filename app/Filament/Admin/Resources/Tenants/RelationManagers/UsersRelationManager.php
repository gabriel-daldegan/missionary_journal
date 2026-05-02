<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Services\TenantPermissionService;
use App\Services\TenantService;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Services\RelationshipJoiner;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name')),
                SelectColumn::make('role')
                    ->label(__('Role'))
                    ->getStateUsing(function (User $user, TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getTenantUserRoles($this->ownerRecord, $user)[0] ?? null;
                    })
                    ->options(function (TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getAllAvailableTenantRolesForDisplay($this->ownerRecord);
                    })
                    ->updateStateUsing(function (User $user, ?string $state, TenantPermissionService $tenantPermissionService) {
                        if ($state === null) {
                            return;
                        }

                        $tenantPermissionService->assignTenantUserRole($this->ownerRecord, $user, $state);

                        Notification::make()
                            ->title(__('User role has been updated.'))
                            ->success()
                            ->send();
                    }),
                IconColumn::make('creator')
                    ->getStateUsing(function (User $user) {
                        return $user->id === $this->ownerRecord->created_by;
                    })
                    ->label(__('Is Creator'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->action(function (array $arguments, array $data, Schema $schema, Table $table, TenantService $tenantService): void {
                        // overwritten from the parent action definition from AttachAction

                        /** @var BelongsToMany $relationship */
                        $relationship = Relation::noConstraints(fn () => $table->getRelationship());

                        $relationshipQuery = app(RelationshipJoiner::class)->prepareQueryForNoConstraints($relationship);

                        $isMultiple = is_array($data['recordId']);

                        $record = $relationshipQuery
                            ->{$isMultiple ? 'whereIn' : 'where'}($relationship->getQualifiedRelatedKeyName(), $data['recordId'])
                            ->{$isMultiple ? 'get' : 'first'}();

                        $result = $tenantService->addUserToTenant($this->ownerRecord, $record, TenancyPermissionConstants::ROLE_USER);

                        if ($result === false) {
                            Notification::make()
                                ->title(__('User could not be added to tenant.'))
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('User has been added to tenant.'))
                                ->success()
                                ->send();
                        }
                    })
                    ->label(__('Add User'))
                    ->modalHeading(__('Add User')),
            ])
            ->recordActions([
                DetachAction::make()
                    ->action(function (User $record, TenantService $tenantService): void {
                        $result = $tenantService->removeUser($this->ownerRecord, $record);

                        if ($result === false) {
                            Notification::make()
                                ->title(__('User could not be removed from tenant.'))
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('User has been removed from tenant.'))
                                ->success()
                                ->send();
                        }
                    })
                    ->disabled(function () {
                        return $this->ownerRecord->users->count() <= 1;
                    })
                    ->label(__('Remove')),
                Action::make('edit')
                    ->url(fn ($record) => EditUser::getUrl(['record' => $record]))
                    ->label(__('Edit'))
                    ->icon('heroicon-o-eye'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
