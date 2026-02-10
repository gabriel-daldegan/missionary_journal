<?php

namespace App\Filament\Dashboard\Resources\Referrals;

use App\Constants\ReferralConstants;
use App\Filament\Dashboard\Resources\Referrals\Pages\ListReferrals;
use App\Models\Referral;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Referral::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referredUser.name')
                    ->label(__('Referred User'))
                    ->searchable(),
                TextColumn::make('referredUser.email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralConstants::STATUS_PENDING => 'gray',
                        ReferralConstants::STATUS_VERIFIED => 'info',
                        ReferralConstants::STATUS_PAID => 'warning',
                        ReferralConstants::STATUS_REWARDED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __(ucfirst($state))),
                TextColumn::make('created_at')
                    ->label(__('Referred On'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('referrer_user_id', auth()->user()->id);
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

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return config('app.referral.enabled', false);
    }

    public static function getNavigationLabel(): string
    {
        return __('My Referrals');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Referrals');
    }

    public static function getModelLabel(): string
    {
        return __('Referral');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Referral::where('referrer_user_id', auth()->user()->id)->count();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Referrals');
    }
}
