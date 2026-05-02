<?php

namespace App\Livewire\Filament\Dashboard;

use App\Models\User;
use App\Services\TeamService;
use App\Services\TenantPermissionService;
use App\Services\TenantService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Users extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $tenant = Filament::getTenant();

                return User::query()
                    ->whereHas('tenants', function (Builder $query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id);
                    });
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                SelectColumn::make('role')
                    ->getStateUsing(function (User $user, TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getTenantUserRoles(Filament::getTenant(), $user)[0] ?? null;
                    })
                    ->options(function (TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getAllAvailableTenantRolesForDisplay(Filament::getTenant());
                    })
                    ->disabled(function (User $user) {
                        return $user->id === auth()->user()->id;
                    })
                    ->updateStateUsing(function (User $user, ?string $state, TenantPermissionService $tenantPermissionService) {
                        if ($state === null) {
                            $tenantPermissionService->removeAllTenantUserRoles(Filament::getTenant(), $user);

                            return;
                        }

                        $tenantPermissionService->assignTenantUserRole(Filament::getTenant(), $user, $state);

                        Notification::make()
                            ->title(__('User role has been updated.'))
                            ->success()
                            ->send();
                    })
                    ->searchable(),
                TextColumn::make('teams')
                    ->getStateUsing(function (User $record, TeamService $teamService) {
                        $teams = $teamService->getUserTeams($record, Filament::getTenant())->pluck('name')->toArray();

                        return implode("\n", $teams);
                    })
                    ->separator("\n")
                    ->visible(fn (): bool => config('app.teams_enabled', false))
                    ->badge()
                    ->label(__('Team(s)')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('remove')
                    ->label(__('Remove User'))
                    ->color('danger')
                    ->requiresConfirmation(true)
                    ->visible(function (User $user, TenantService $tenantService) {
                        return $tenantService->canRemoveUser(Filament::getTenant(), $user);
                    })
                    ->action(function (User $user, TenantService $tenantService) {
                        $result = $tenantService->removeUser(Filament::getTenant(), $user);

                        if ($result) {
                            Notification::make()
                                ->title(__('User has been removed.'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('User could not be removed.'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.filament.dashboard.users');
    }
}
