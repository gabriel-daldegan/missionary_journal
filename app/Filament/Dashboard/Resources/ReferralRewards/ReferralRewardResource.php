<?php

namespace App\Filament\Dashboard\Resources\ReferralRewards;

use App\Constants\ReferralConstants;
use App\Filament\Dashboard\Resources\ReferralRewards\Pages\ListReferralRewards;
use App\Models\ReferralReward;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralRewardResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = ReferralReward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reward_type')
                    ->label(__('Reward Type'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ReferralConstants::REWARD_TYPE_COUPON => __('Coupon'),
                        ReferralConstants::REWARD_TYPE_CUSTOM_EVENT => __('Custom Reward'),
                        default => __('Unknown'),
                    })
                    ->badge(),
                TextColumn::make('discountCode.code')
                    ->label(__('Coupon Code'))
                    ->copyable()
                    ->copyMessage(__('Coupon code copied to clipboard!'))
                    ->placeholder(__('N/A'))
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record || $record->reward_type !== ReferralConstants::REWARD_TYPE_COUPON) {
                            return __('N/A');
                        }

                        return $state;
                    }),
                TextColumn::make('discountCode.discount.name')
                    ->label(__('Details'))
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record || $record->reward_type !== ReferralConstants::REWARD_TYPE_COUPON) {
                            return __('N/A');
                        }

                        $discount = $record->discountCode?->discount;

                        if (! $discount) {
                            return __('N/A');
                        }

                        $details = __('Discount of ');
                        if ($discount->type === 'percentage') {
                            $details .= $discount->amount.'%';
                        } else {
                            $details .= money($discount->amount, config('app.default_currency'));
                        }

                        return $details;
                    }),
                TextColumn::make('created_at')
                    ->label(__('Earned On'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('referrer_user_id', auth()->user()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralRewards::route('/'),
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
        return __('My Rewards');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Referral Rewards');
    }

    public static function getModelLabel(): string
    {
        return __('Referral Reward');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Referrals');
    }
}
