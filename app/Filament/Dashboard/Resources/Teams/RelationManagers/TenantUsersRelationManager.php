<?php

namespace App\Filament\Dashboard\Resources\Teams\RelationManagers;

use App\Models\TenantUser;
use App\Services\TeamService;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'tenantUsers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Team Members'))
            ->recordTitle(fn (TenantUser $record): string => "{$record->user->name}")
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Name'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->multiple()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()))
                    ->label(__('Add Team Member'))
                    ->attachAnother(false)
                    ->preloadRecordSelect(true)
                    ->modalHeading(__('Add Member to Team'))
                    ->modalSubmitActionLabel(__('Add Team Member'))
                    ->after(function ($data, TeamService $teamService) {
                        foreach ($data['recordId'] as $tenantUserId) {
                            $tenantUser = TenantUser::find($tenantUserId);
                            $user = $tenantUser->user;
                            $tenant = $tenantUser->tenant;
                            $team = $this->ownerRecord;

                            $teamService->userJoinedTeam($user, $team, $tenant);
                        }
                    }),
            ])
            ->recordActions([
                DetachAction::make()
                    ->modalSubmitActionLabel(__('Remove Team Member'))
                    ->modalHeading(fn ($record): string => __('Remove :label', ['label' => $record->user->name]))
                    ->label(__('Remove Team Member')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label(__('Remove Team Members')),
                ]),
            ]);
    }
}
