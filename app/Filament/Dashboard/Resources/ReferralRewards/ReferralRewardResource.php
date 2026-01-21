<?php

namespace App\Filament\Dashboard\Resources\ReferralRewards;

use App\Constants\ReferralConstants;
use App\Filament\Dashboard\Resources\ReferralRewards\Pages\ListReferralRewards;
use App\Models\ReferralReward;
use App\Services\ConfigService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralRewardResource extends Resource
{
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
                    ->placeholder(__('N/A'))
                    ->visible(fn ($record) => $record && $record->reward_type === ReferralConstants::REWARD_TYPE_COUPON),
                TextColumn::make('metadata')
                    ->label(__('Details'))
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record || $record->reward_type !== ReferralConstants::REWARD_TYPE_COUPON) {
                            return __('Custom Event Triggered');
                        }

                        if (is_array($state) && isset($state['discount_name'])) {
                            $details = $state['discount_name'].' - ';
                            if ($state['discount_type'] === 'percentage') {
                                $details .= $state['discount_amount'].'%';
                            } else {
                                $details .= money($state['discount_amount'], config('app.default_currency'));
                            }

                            return $details;
                        }

                        return __('N/A');
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

    public static function isDiscovered(): bool
    {
        $configService = app()->make(ConfigService::class);

        return (bool) $configService->get('app.referral.enabled', false);
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
