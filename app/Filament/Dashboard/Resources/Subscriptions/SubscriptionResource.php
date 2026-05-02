<?php

namespace App\Filament\Dashboard\Resources\Subscriptions;

use App\Constants\DiscountConstants;
use App\Constants\PlanPriceTierConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Subscriptions\ActionHandlers\DiscardSubscriptionCancellationActionHandler;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\AddDiscount;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\CancelSubscription;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\ChangeSubscriptionPlan;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\ConfirmCancelSubscription;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\PaymentProviders\Paddle\PaddleUpdatePaymentDetails;
use App\Filament\Dashboard\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\Dashboard\Resources\Subscriptions\RelationManagers\UsagesRelationManager;
use App\Mapper\SubscriptionStatusMapper;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\TenantPermissionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('Plan')),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(function (string $state, Subscription $record) {
                        $interval = $record->interval->name;
                        if ($record->interval_count > 1) {
                            $interval = $record->interval_count.' '.__(str()->of($record->interval->name)->plural()->toString());
                        }

                        $formatted = money($state, $record->currency->code).' / '.$interval;

                        if ($record->price_type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                            $planPrice = $record->plan->prices()->where('currency_id', $record->currency_id)->first();

                            if ($planPrice !== null) {
                                $formatted .= ' ('.__('Includes :count seats, +:price/extra seat', [
                                    'count' => $planPrice->included_seats,
                                    'price' => money($planPrice->extra_seat_price, $record->currency->code),
                                ]).')';
                            }
                        } elseif ($record->plan->type === PlanType::SEAT_BASED->value) {
                            $formatted .= ' / '.__('seat');
                        }

                        return $formatted;
                    }),
                TextColumn::make('ends_at')->dateTime(config('app.datetime_format'))->label(__('Next Renewal')),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->color(fn (Subscription $record, SubscriptionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                    ->badge()
                    ->formatStateUsing(fn (string $state, SubscriptionStatusMapper $mapper): string => $mapper->mapForDisplay($state)),
                IconColumn::make('is_canceled_at_end_of_cycle')
                    ->label(__('Renews automatically'))
                    ->icon(function ($state) {
                        $state = boolval($state);

                        return $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('verify-phone')
                    ->button()
                    ->color('warning')
                    ->icon('heroicon-s-phone')
                    ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->subscriptionRequiresUserVerification($record))
                    ->url(fn (Subscription $record): string => route('user.phone-verify'))
                    ->label(__('Verify Phone Number')),
                Action::make('complete-subscription')
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-s-wallet')
                    ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->isIncompleteSubscription($record))
                    ->url(fn (Subscription $record): string => route('checkout.convert-local-subscription', ['subscriptionUuid' => $record->uuid]))
                    ->label(__('Complete Subscription')),
                ViewAction::make()
                    ->label(__('View Details')),
                ActionGroup::make([
                    Action::make('change-plan')
                        ->label(__('Change Plan'))
                        ->icon('heroicon-o-rocket-launch')
                        ->url(fn (Subscription $record): string => SubscriptionResource::getUrl('change-plan', ['record' => $record->uuid]))
                        ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->canChangeSubscriptionPlan($record)),
                    Action::make('cancel')
                        ->label(__('Cancel Subscription'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->canCancelSubscription($record))
                        ->url(fn (Subscription $record): string => SubscriptionResource::getUrl('cancel', ['record' => $record->uuid])),
                    Action::make('discard-cancellation')
                        ->label(__('Discard Cancellation'))
                        ->icon('heroicon-m-x-circle')
                        ->action(function ($record, DiscardSubscriptionCancellationActionHandler $handler) {
                            $handler->handle($record);
                        })->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->canDiscardSubscriptionCancellation($record)),
                ]),
            ])
            ->toolbarActions([
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'usages' => UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'view' => ViewSubscription::route('/{record}'),
            'change-plan' => ChangeSubscriptionPlan::route('/{record}/change-plan'),
            'cancel' => CancelSubscription::route('/{record}/cancel'),
            'confirm-cancellation' => ConfirmCancelSubscription::route('/{record}/confirm-cancellation'),
            'add-discount' => AddDiscount::route('/{record}/add-discount'),
            'paddle.update-payment-details' => PaddleUpdatePaymentDetails::route('/paddle/update-payment-details'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', Filament::getTenant()->id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Subscription Details'))
                    ->description(__('View details about your subscription.'))
                    ->schema([
                        ViewEntry::make('status')
                            ->label(__('Status'))
                            ->visible(fn (Subscription $record): bool => $record->status === SubscriptionStatus::PAST_DUE->value)
                            ->view('filament.common.infolists.entries.warning', [
                                'message' => __('Your subscription is past due. Please update your payment details.'),
                            ]),
                        TextEntry::make('plan.name')->label(__('Plan')),
                        TextEntry::make('price')
                            ->label(__('Price'))
                            ->formatStateUsing(function (string $state, Subscription $record) {
                                $interval = $record->interval->name;
                                if ($record->interval_count > 1) {
                                    $interval = $record->interval_count.' '.__(str()->of($record->interval->name)->plural()->toString());
                                }

                                $formatted = money($state, $record->currency->code).' / '.$interval;

                                if ($record->price_type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                                    $planPrice = $record->plan->prices()->where('currency_id', $record->currency_id)->first();

                                    if ($planPrice !== null) {
                                        $formatted .= ' ('.__('Includes :count seats, +:price/extra seat', [
                                            'count' => $planPrice->included_seats,
                                            'price' => money($planPrice->extra_seat_price, $record->currency->code),
                                        ]).')';
                                    }
                                } elseif ($record->plan->type === PlanType::SEAT_BASED->value) {
                                    $formatted .= ' / '.__('seat');
                                }

                                return $formatted;
                            }),
                        TextEntry::make('price_per_unit')
                            ->label(__('Price per Unit'))
                            ->visible(fn (Subscription $record): bool => $record->price_type === PlanPriceType::USAGE_BASED_PER_UNIT->value && $record->price_per_unit !== null)
                            ->formatStateUsing(function (string $state, $record) {
                                return money($state, $record->currency->code).' / '.__($record->plan->meter->name);
                            }),
                        TextEntry::make('price_tiers')
                            ->label(__('Price Tiers'))
                            ->visible(fn (Subscription $record): bool => in_array($record->price_type, [PlanPriceType::USAGE_BASED_TIERED_VOLUME->value, PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value]) && $record->price_tiers !== null)
                            ->getStateUsing(function (Subscription $record) {
                                $start = 0;
                                $unitMeterName = $record->plan->meter->name;
                                $currencyCode = $record->currency->code;
                                $output = '';
                                $startingPhrase = __('From');
                                foreach ($record->price_tiers as $tier) {
                                    $output .= $startingPhrase.' '.$start.' - '.$tier[PlanPriceTierConstants::UNTIL_UNIT].' '.__(str()->plural($unitMeterName)).' → '.money($tier[PlanPriceTierConstants::PER_UNIT], $currencyCode).' / '.__($unitMeterName);
                                    if ($tier[PlanPriceTierConstants::FLAT_FEE] > 0) {
                                        $output .= ' + '.money($tier[PlanPriceTierConstants::FLAT_FEE], $currencyCode);
                                    }
                                    $start = intval($tier[PlanPriceTierConstants::UNTIL_UNIT]) + 1;
                                    $output .= '<br>';

                                    if ($record->price_type === PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value) {
                                        $startingPhrase = __('Next');
                                    }
                                }

                                return new HtmlString($output);
                            }),
                        TextEntry::make('ends_at')
                            ->dateTime(config('app.datetime_format'))
                            ->label(__('Next Renewal'))->visible(fn (Subscription $record): bool => ! $record->is_canceled_at_end_of_cycle),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->color(fn (Subscription $record, SubscriptionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                            ->badge()
                            ->formatStateUsing(fn (string $state, SubscriptionStatusMapper $mapper): string => $mapper->mapForDisplay($state)),
                        TextEntry::make('is_canceled_at_end_of_cycle')
                            ->label(__('Renews automatically'))
                            ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->canCancelSubscription($record))
                            ->icon(
                                function ($state) {
                                    $state = boolval($state);

                                    return $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
                                })
                            ->formatStateUsing(
                                function ($state) {
                                    return boolval($state) ? __('No') : __('Yes');
                                }),
                    ])->columnSpanFull(),
                Section::make(__('Discount Details'))
                    ->hidden(fn (Subscription $record): bool => $record->discounts->isEmpty() ||
                        ($record->discounts[0]->valid_until !== null && $record->discounts[0]->valid_until < now())
                    )
                    ->description(__('View details about your discount.'))
                    ->schema([
                        TextEntry::make('discounts.amount')
                            ->label(__('Discount Amount'))
                            ->formatStateUsing(function (string $state, $record) {
                                if ($record->discounts[0]->type === DiscountConstants::TYPE_PERCENTAGE) {
                                    return $state.'%';
                                }

                                return money($state, $record->discounts[0]->code);
                            }),

                        TextEntry::make('discounts.valid_until')
                            ->dateTime(config('app.datetime_format'))
                            ->visible(fn (Subscription $record): bool => $record->discounts[0]->valid_until !== null)
                            ->label(__('Valid Until')),
                    ])->columnSpanFull(),

            ]);
    }

    public static function canAccess(): bool
    {
        /** @var TenantPermissionService $tenantPermissionService */
        $tenantPermissionService = app(TenantPermissionService::class);

        return config('app.customer_dashboard.show_subscriptions', false) && $tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            auth()->user(),
            TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS,
        );
    }

    public static function getNavigationLabel(): string
    {
        return __('Subscriptions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Subscriptions');
    }

    public static function getModelLabel(): string
    {
        return __('Subscription');
    }
}
