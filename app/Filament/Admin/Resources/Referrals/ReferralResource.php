<?php

namespace App\Filament\Admin\Resources\Referrals;

use App\Constants\ReferralConstants;
use App\Filament\Admin\Resources\Referrals\Pages\ListReferrals;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Referral;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Referrals');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Referrals');
    }

    public static function getModelLabel(): string
    {
        return __('Referral');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referrer.name')
                    ->label(__('Referrer'))
                    ->description(fn (Referral $record): string => $record->referrer?->email ?? '')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Referral $record): ?string => $record->referrer
                        ? UserResource::getUrl('edit', ['record' => $record->referrer])
                        : null
                    ),
                TextColumn::make('referredUser.name')
                    ->label(__('Referred User'))
                    ->description(fn (Referral $record): string => $record->referredUser?->email ?? '')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Referral $record): ?string => $record->referredUser
                        ? UserResource::getUrl('edit', ['record' => $record->referredUser])
                        : null
                    ),
                TextColumn::make('referral_code')
                    ->label(__('Code Used'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ReferralConstants::STATUS_PENDING => __('Pending'),
                        ReferralConstants::STATUS_VERIFIED => __('Verified'),
                        ReferralConstants::STATUS_PAID => __('Paid'),
                        ReferralConstants::STATUS_REWARDED => __('Rewarded'),
                        default => __('Unknown'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        ReferralConstants::STATUS_PENDING => 'gray',
                        ReferralConstants::STATUS_VERIFIED => 'info',
                        ReferralConstants::STATUS_PAID => 'warning',
                        ReferralConstants::STATUS_REWARDED => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('verified_at')
                    ->label(__('Verified At'))
                    ->dateTime(config('app.datetime_format'))
                    ->placeholder(__('N/A'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->dateTime(config('app.datetime_format'))
                    ->placeholder(__('N/A'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rewarded_at')
                    ->label(__('Rewarded At'))
                    ->dateTime(config('app.datetime_format'))
                    ->placeholder(__('N/A'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        ReferralConstants::STATUS_PENDING => __('Pending'),
                        ReferralConstants::STATUS_VERIFIED => __('Verified'),
                        ReferralConstants::STATUS_PAID => __('Paid'),
                        ReferralConstants::STATUS_REWARDED => __('Rewarded'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferrals::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Referral::count();
    }
}
