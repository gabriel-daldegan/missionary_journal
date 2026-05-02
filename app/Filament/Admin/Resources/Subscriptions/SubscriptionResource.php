<?php

namespace App\Filament\Admin\Resources\Subscriptions;

use App\Constants\DiscountConstants;
use App\Constants\PlanPriceTierConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Filament\Admin\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Admin\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\Admin\Resources\Subscriptions\RelationManagers\UsagesRelationManager;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Mapper\SubscriptionStatusMapper;
use App\Models\Subscription;
use App\Services\CurrencyService;
use App\Services\SubscriptionService;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static array $cachedSubscriptionHistoryComponents = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Revenue');
    }

    public static function form(Schema $schema): Schema
    {
        /** @var CurrencyService $currencyService */
        $currencyService = resolve(CurrencyService::class);

        return $schema
            ->components([
                Tabs::make('Subscription')
                    ->columnSpan('full')
                    ->tabs([
                        Tab::make(__('Details'))
                            ->schema([
                                Section::make()->schema([
                                    Select::make('user_id')
                                        ->label(__('User'))
                                        ->relationship('user', 'name')
                                        ->preload()
                                        ->required(),
                                    Select::make('plan_id')
                                        ->label(__('Plan'))
                                        ->relationship('plan', 'name')
                                        ->preload()
                                        ->required(),
                                    TextInput::make('price')
                                        ->label(__('Price'))
                                        ->required(),
                                    Select::make('currency_id')
                                        ->options(
                                            $currencyService->getAllCurrencies()
                                                ->mapWithKeys(function ($currency) {
                                                    return [$currency->id => $currency->name.' ('.$currency->symbol.')'];
                                                })
                                                ->toArray()
                                        )
                                        ->label(__('Currency'))
                                        ->required(),
                                    DateTimePicker::make('renew_at')
                                        ->label(__('Next Renewal'))
                                        ->displayFormat(config('app.datetime_format')),
                                    DateTimePicker::make('cancelled_at')
                                        ->label(__('Cancelled At'))
                                        ->displayFormat(config('app.datetime_format')),
                                    DateTimePicker::make('grace_period_ends_at')
                                        ->label(__('Grace Period Ends At'))
                                        ->displayFormat(config('app.datetime_format')),
                                    Toggle::make('is_active')
                                        ->label(__('Active'))
                                        ->required(),
                                    Toggle::make('is_trial_active')
                                        ->label(__('Trial Active'))
                                        ->required(),
                                ]),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label(__('Tenant'))->searchable(),
                TextColumn::make('plan.name')->label(__('Plan'))->searchable(),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(function (string $state, $record) {
                        $interval = $record->interval->name;
                        if ($record->interval_count > 1) {
                            $interval = $record->interval_count.' '.__(str()->of($record->interval->name)->plural()->toString());
                        }

                        if ($record->plan->type === PlanType::SEAT_BASED->value) {
                            $interval .= ' / '.__('seat');
                        }

                        return money($state, $record->currency->code).' / '.$interval;
                    }),
                TextColumn::make('payment_provider_id')
                    ->formatStateUsing(function (string $state, $record) {
                        return $record->paymentProvider->name;
                    })
                    ->label(__('Payment Provider'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (Subscription $record, SubscriptionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                    ->formatStateUsing(
                        function (string $state, $record, SubscriptionStatusMapper $mapper) {
                            return $mapper->mapForDisplay($state);
                        })
                    ->searchable(),
                TextColumn::make('created_at')->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),
                TextColumn::make('updated_at')->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->headerActions([

            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'user',
                'currency',
                'paymentProvider',
                'interval',
            ]))
            ->toolbarActions([
            ])->defaultSort('created_at', 'desc');
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
        ];
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

    /**
     * Filament schema is called multiple times for some reason, so we need to cache the components to avoid performance issues.
     */
    public static function subscriptionHistoryComponents($record): array
    {
        if (! empty(static::$cachedSubscriptionHistoryComponents)) {
            return static::$cachedSubscriptionHistoryComponents;
        }

        $i = 0;
        foreach ($record->versions->reverse() as $version) {
            $versionModel = $version->getModel();

            $user = $versionModel->user;
            $plan = $versionModel->plan;

            static::$cachedSubscriptionHistoryComponents[] = Section::make([
                TextEntry::make('plan_name_'.$i)
                    ->label(__('Plan'))
                    ->getStateUsing(fn () => $plan->name),

                TextEntry::make('status_'.$i)
                    ->label(__('Status'))
                    ->color(fn ($record, SubscriptionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                    ->badge()
                    ->getStateUsing(fn ($record, SubscriptionStatusMapper $mapper): string => $mapper->mapForDisplay($record->status)),

                TextEntry::make('changed_by_'.$i)
                    ->label(__('Changed By'))
                    ->getStateUsing(fn () => $user->name),

                TextEntry::make('ends_at_'.$i)
                    ->label(__('Ends At'))
                    ->getStateUsing(fn () => date(config('app.datetime_format'), strtotime($versionModel->ends_at))),

                TextEntry::make('payment_provider_status_'.$i)
                    ->color('info')
                    ->label(__('Payment Provider Status'))
                    ->badge()
                    ->getStateUsing(fn () => $versionModel->payment_provider_status ?? '-'),

            ])->columns(5)->collapsible()->heading(
                date(config('app.datetime_format'), strtotime($version->created_at))
            );

            $i++;
        }

        return static::$cachedSubscriptionHistoryComponents;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Subscription')
                    ->columnSpan('full')
                    ->tabs([
                        Tab::make(__('Details'))
                            ->schema([
                                Section::make(__('Subscription Details'))
                                    ->description(__('View details about subscription.'))
                                    ->schema([
                                        ViewEntry::make('status')
                                            ->label(__('Status'))
                                            ->visible(fn (Subscription $record): bool => $record->status === SubscriptionStatus::PAST_DUE->value)
                                            ->view('filament.common.infolists.entries.warning', [
                                                'message' => __('Subscription is past due.'),
                                            ]),
                                        TextEntry::make('plan.name')
                                            ->label(__('Plan')),
                                        TextEntry::make('price')
                                            ->label(__('Price'))
                                            ->formatStateUsing(function (string $state, $record) {
                                                $interval = $record->interval->name;
                                                if ($record->interval_count > 1) {
                                                    $interval = $record->interval_count.' '.__(str()->of($record->interval->name)->plural()->toString());
                                                }

                                                if ($record->plan->type === PlanType::SEAT_BASED->value) {
                                                    $interval .= ' / '.__('seat');
                                                }

                                                return money($state, $record->currency->code).' / '.$interval;
                                            }),
                                        TextEntry::make('price_per_unit')
                                            ->label(__('Price Per Unit'))
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
                                        TextEntry::make('payment_provider_id')
                                            ->formatStateUsing(function (string $state, $record) {
                                                return $record->paymentProvider->name;
                                            })
                                            ->label(__('Payment Provider')),
                                        TextEntry::make('payment_provider_subscription_id')
                                            ->label(__('Payment Provider Subscription ID')),
                                        TextEntry::make('ends_at')->dateTime(config('app.datetime_format'))->label(__('Next Renewal'))->visible(fn (Subscription $record): bool => ! $record->is_canceled_at_end_of_cycle),
                                        TextEntry::make('trial_ends_at')->dateTime(config('app.datetime_format'))->label(__('Trial Ends At'))->visible(fn (Subscription $record): bool => $record->trial_ends_at !== null),
                                        TextEntry::make('status')
                                            ->label(__('Status'))
                                            ->badge()
                                            ->color(fn (Subscription $record, SubscriptionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                                            ->formatStateUsing(fn (string $state, SubscriptionStatusMapper $mapper): string => $mapper->mapForDisplay($state)),
                                        TextEntry::make('type')->badge()->color('info')
                                            ->label(__('Type'))
                                            ->formatStateUsing(
                                                function (string $state) {
                                                    switch ($state) {
                                                        case SubscriptionType::PAYMENT_PROVIDER_MANAGED:
                                                            return __('Payment Provider Managed');
                                                        case SubscriptionType::LOCALLY_MANAGED:
                                                            return __('Locally Managed');
                                                    }

                                                    return $state;
                                                }),
                                        TextEntry::make('is_canceled_at_end_of_cycle')
                                            ->label(__('Renews automatically'))
                                            ->visible(fn (Subscription $record, SubscriptionService $subscriptionService): bool => $subscriptionService->canCancelSubscription($record))
                                            ->icon(function ($state) {
                                                $state = boolval($state);

                                                return $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
                                            })->formatStateUsing(
                                                function ($state) {
                                                    return boolval($state) ? __('No') : __('Yes');
                                                }),
                                        TextEntry::make('cancellation_reason')
                                            ->label(__('Cancellation Reason'))
                                            ->visible(fn (Subscription $record): bool => $record->cancellation_reason !== null),
                                        TextEntry::make('cancellation_additional_info')
                                            ->label(__('Cancellation Additional Info'))
                                            ->visible(fn (Subscription $record): bool => $record->cancellation_additional_info !== null),
                                        TextEntry::make('tenant.name')
                                            ->url(fn (Subscription $record) => EditTenant::getUrl(['record' => $record->tenant]))
                                            ->label(__('Tenant')),
                                        TextEntry::make('quantity')
                                            ->label(__('Quantity'))
                                            ->visible(fn (Subscription $record): bool => $record->plan->type === PlanType::SEAT_BASED->value),
                                        TextEntry::make('comments')
                                            ->label(__('Comments'))
                                            ->html()
                                            ->visible(fn (Subscription $record): bool => $record->comments !== null && $record->comments !== ''),
                                    ]),
                                Section::make(__('Discount Details'))
                                    ->hidden(fn (Subscription $record): bool => $record->discounts->isEmpty() ||
                                        ($record->discounts[0]->valid_until !== null && $record->discounts[0]->valid_until < now())
                                    )
                                    ->description(__('View details about subscription discount.'))
                                    ->schema([
                                        TextEntry::make('discounts.amount')
                                            ->label(__('Discount Amount'))
                                            ->formatStateUsing(function (string $state, $record) {
                                                if ($record->discounts[0]->type === DiscountConstants::TYPE_PERCENTAGE) {
                                                    return $state.'%';
                                                }

                                                return money($state, $record->discounts[0]->code);
                                            }),

                                        TextEntry::make('discounts.valid_until')->dateTime(config('app.datetime_format'))->label(__('Valid Until')),
                                    ]),

                            ]),
                        Tab::make(__('Changes'))
                            ->schema(
                                function ($record) {
                                    // Filament schema is called multiple times for some reason, so we need to cache the components to avoid performance issues.
                                    return static::subscriptionHistoryComponents($record);
                                },
                            ),
                    ]),

            ]);

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
