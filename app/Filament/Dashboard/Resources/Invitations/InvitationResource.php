<?php

namespace App\Filament\Dashboard\Resources\Invitations;

use App\Constants\InvitationStatus;
use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Dashboard\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Dashboard\Resources\Invitations\Pages\ListInvitations;
use App\Mapper\InvitationStatusMapper;
use App\Models\Invitation;
use App\Services\TenantPermissionService;
use App\Services\TenantService;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::Plus;

    public static function getNavigationGroup(): ?string
    {
        return __('Team Management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('email')
                    ->label(__('Emails'))
                    ->placeholder(__('email1@example.com, email2@example.com'))
                    ->required()
                    ->helperText(__('Enter email addresses separated by commas or new lines.'))
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $emails = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

                            if (empty($emails)) {
                                $fail(__('Please enter at least one email address.'));

                                return;
                            }

                            foreach ($emails as $email) {
                                $email = trim($email);
                                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $fail(__('The email :email is invalid.', ['email' => $email]));

                                    continue;
                                }

                                // if there is a user with this email address in the tenant and status is pending and expires_at is greater than now, fail
                                if (
                                    Filament::getTenant()->invitations()
                                        ->where('email', $email)
                                        ->where('status', InvitationStatus::PENDING->value)
                                        ->where('expires_at', '>', now())
                                        ->exists()
                                ) {
                                    $fail(__('The email :email has already been invited.', ['email' => $email]));
                                }

                                if (Filament::getTenant()->users()->where('email', $email)->exists()) {
                                    $fail(__('The user with email :email is already in the team.', ['email' => $email]));
                                }
                            }

                            /** @var TenantService $tenantService */
                            $tenantService = app(TenantService::class);

                            if (! $tenantService->canInviteUsers(Filament::getTenant(), count($emails))) {
                                $fail(__('You have reached the maximum number of users allowed for your subscription.'));
                            }
                        },
                    ]),
                Select::make('role')
                    ->options(function (TenantPermissionService $tenantPermissionService) {
                        return $tenantPermissionService->getAllAvailableTenantRolesForDisplay(Filament::getTenant());
                    })
                    ->default(TenancyPermissionConstants::ROLE_USER)
                    ->label(__('Role'))
                    ->helperText(__('Choose the role for this user.')),
                Select::make('team_id')
                    ->label(__('Team'))
                    ->visible(fn (): bool => config('app.teams_enabled', false))
                    ->helperText(__('Select the team to which the user will be invited.'))
                    ->options(function () {
                        return Filament::getTenant()->teams()->pluck('name', 'id')->toArray();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(__('Send invitations to people to join your workspace.'))
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label(__('Inviter'))
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user->name;
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(function ($state, InvitationStatusMapper $invitationStatusMapper) {
                        return $invitationStatusMapper->mapForDisplay($state);
                    }),
                TextColumn::make('team_id')
                    ->label(__('Team'))
                    ->default('-')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->team?->name ?? '-';
                    })
                    ->visible(fn (): bool => config('app.teams_enabled', false))
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->default('-')
                    ->formatStateUsing(function ($state) {
                        return Str::of($state)->title();
                    })
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('Expires At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        /** @var TenantPermissionService $tenantPermissionService */
        $tenantPermissionService = app(TenantPermissionService::class); // a bit ugly, but this is the Filament way :/

        return config('app.allow_tenant_invitations', false) && $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS,
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
            'edit' => EditInvitation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Invitations');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', Filament::getTenant()->id)->where('expires_at', '>', now())->where('status', InvitationStatus::PENDING->value);
    }
}
