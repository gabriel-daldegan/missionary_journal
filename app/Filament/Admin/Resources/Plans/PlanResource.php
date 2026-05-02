<?php

namespace App\Filament\Admin\Resources\Plans;

use App\Constants\PlanType;
use App\Filament\Admin\Resources\Plans\Pages\CreatePlan;
use App\Filament\Admin\Resources\Plans\Pages\EditPlan;
use App\Filament\Admin\Resources\Plans\Pages\ListPlans;
use App\Filament\Admin\Resources\Plans\RelationManagers\PaymentProviderDataRelationManager;
use App\Filament\Admin\Resources\Plans\RelationManagers\PricesRelationManager;
use App\Mapper\PlanTypeMapper;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Product Management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Plans');
    }

    public static function getModelLabel(): string
    {
        return __('Plan');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->required()
                        ->label(__('Name'))
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->nullable()
                        ->label(__('Slug'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                $product = Product::find($get('product_id'));
                                $interval = Interval::find($get('interval_id'));
                                $intervalCount = $get('interval_count');
                                $intervalCountPart = $intervalCount > 1 ? '-'.$intervalCount : '';
                                $intervalPart = $interval ? $intervalCountPart.'-'.$interval->adverb : '';

                                // add a random string if there is a plan with the same slug
                                $state = Str::slug($product->name.$intervalPart);
                                if (Plan::where('slug', $state)->exists()) {
                                    $state .= '-'.Str::random(5);
                                }

                                return Str::slug($state);
                            }

                            return $state;
                        })
                        ->helperText(__('Leave empty to generate slug automatically from product name & interval.'))
                        ->maxLength(255)
                        ->rules(['alpha_dash'])
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    Radio::make('type')
                        ->label(__('Type'))
                        ->helperText(
                            new HtmlString(
                                __('Flat Rate: Fixed price per billing cycle. Seat Based: Charge per seat/user for each billing cycle. Usage Based: Price per unit with optional tiers.').'<br><strong>'.__('Important').'</strong>: '.__('Usage-based pricing is not supported for Paddle.')
                            )
                        )
                        ->options([
                            PlanType::FLAT_RATE->value => __('Flat Rate'),
                            PlanType::SEAT_BASED->value => __('Seat-Based'),
                            PlanType::USAGE_BASED->value => __('Usage-based'),
                        ])
                        ->default(PlanType::FLAT_RATE->value)
                        ->disabledOn('edit')
                        ->live()
                        ->required(),
                    TextInput::make('max_users_per_tenant')
                        ->label(__('Max Users Per Tenant'))
                        ->helperText(__('The maximum number of users that can be added to a tenant (team). Use that to limited the number of users that can use this plan for each tenant. Use "0" for unlimited.'))
                        ->integer()
                        ->default(0)
                        ->minValue(0)
                        ->required(),
                    Select::make('meter_id')
                        ->label(__('Meter'))
                        ->relationship('meter', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->helperText(__('The name of the meter. Please use singular form, for example: "Token" instead of "Tokens".'))
                                ->required()
                                ->maxLength(255),
                        ])
                        ->visible(function (Get $get) {
                            return $get('type') === PlanType::USAGE_BASED->value;
                        })
                        ->required(function (Get $get) {
                            return $get('type') === PlanType::USAGE_BASED->value;
                        }),
                    Select::make('product_id')
                        // only products with is_default = false can be selected
                        ->relationship('product', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_default', false))
                        ->label(__('Product'))
                        ->required()
                        ->preload(),
                    Grid::make(2)->schema([
                        TextInput::make('interval_count')
                            ->label(__('Interval Count'))
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->helperText(__('The number of intervals (weeks, months, etc) between each billing cycle.')),
                        Select::make('interval_id')
                            ->label(__('Interval'))
                            ->relationship('interval', 'name')
                            ->options(function () {
                                return Interval::all()->mapWithKeys(fn ($interval) => [$interval->id => __($interval->name)]);
                            })
                            ->helperText(__('The interval (week, month, etc) between each billing cycle.'))
                            ->required()
                            ->preload(),
                    ])->hidden(
                        fn (Get $get): bool => $get('is_default') === true
                    ),
                    Toggle::make('has_trial')
                        ->reactive()
                        ->label(__('Has Trial'))
                        ->requiredWith('trial_interval_id')
                        ->afterStateUpdated(
                            fn ($state, callable $set) => $state ? $set('trial_interval_id', null) : $set('trial_interval_id', 'hidden')
                        )
                        ->hidden(
                            fn (Get $get): bool => $get('is_default') === true
                        ),
                    Grid::make(2)->schema([
                        TextInput::make('trial_interval_count')
                            ->required()
                            ->integer()
                            ->label(__('Trial Interval Count'))
                            ->minValue(1)
                            ->required(
                                fn (Get $get): bool => $get('has_trial') === true
                            )
                            ->hidden(
                                fn (Get $get): bool => $get('has_trial') === false
                            ),
                        Select::make('trial_interval_id')
                            ->relationship('trialInterval', 'name')
                            ->label(__('Trial Interval'))
                            ->options(function () {
                                return Interval::all()->mapWithKeys(fn ($interval) => [$interval->id => __($interval->name)]);
                            })
                            ->requiredWith('has_trial')
                            ->preload()
                            ->required(
                                fn (Get $get): bool => $get('has_trial') === true
                            )
                            ->hidden(
                                fn (Get $get): bool => $get('has_trial') === false
                            ),
                    ])->hidden(
                        fn (Get $get): bool => $get('is_default') === true
                    ),
                    Toggle::make('is_active')
                        ->label(__('Is Active'))
                        ->helperText('Whether the plan should be active or not.')
                        ->default(true)
                        ->required(),
                    Toggle::make('is_visible')
                        ->label(__('Is Visible'))
                        ->default(true)
                        ->helperText('If true, then this plan will be visible in the components that show the plans on the frontend. If this is disabled, the plan will be hidden in the components that show the plans on the frontend, but users who have the plan URL will still be able to purchase it.')
                        ->required(),
                    RichEditor::make('description'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(__('Plans are the different tiers of your product that you offer to your customers.'))
            ->description(__('For example: if you have Starter, Pro and Premium products, you would create a monthly and yearly plans for each of those to offer them in different intervals.'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('Name')),
                TextColumn::make('slug')->searchable()->sortable()->label(__('Slug')),
                TextColumn::make('product.name')
                    ->label(__('Product')),
                TextColumn::make('interval')->formatStateUsing(function (string $state, $record) {
                    return $record->interval_count.' '.$record->interval->name;
                })->label(__('Interval')),
                TextColumn::make('type')->formatStateUsing(function (string $state, $record, PlanTypeMapper $mapper) {
                    return $mapper->mapForDisplay($state);
                })->label(__('Type')),
                TextColumn::make('has_trial')->formatStateUsing(function (string $state, $record) {
                    if ($record->has_trial) {
                        return $record->trial_interval_count.' '.$record->trialInterval->name;
                    }

                    return '-';
                })->label(__('Trial')),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                IconColumn::make('prices_exists')
                    ->exists('prices')
                    ->label(__('Has Prices'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'interval',
                'trialInterval',
            ]))
            ->toolbarActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
            PaymentProviderDataRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Plans');
    }
}
