<?php

namespace App\Filament\Admin\Resources\Plans\RelationManagers;

use App\Constants\PlanPriceTierConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Mapper\PlanPriceMapper;
use App\Models\Currency;
use App\Services\CurrencyService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Throwable;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $recordTitleAttribute = 'id';

    private CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function form(Schema $schema): Schema
    {
        $defaultCurrency = $this->currencyService->getCurrency()->id;

        return $schema
            ->components([
                Section::make([
                    Radio::make('type')
                        ->helperText(__('Pick the price type for this plan.'))
                        ->label(__('Type'))
                        ->options(function () {
                            return PlanPriceMapper::getPlanPriceTypes($this->ownerRecord->type);
                        })
                        ->default(array_keys(PlanPriceMapper::getPlanPriceTypes($this->ownerRecord->type))[0])
                        ->visible(function () {
                            return $this->ownerRecord->type === PlanType::USAGE_BASED->value
                                || $this->ownerRecord->type === PlanType::SEAT_BASED->value;
                        })
                        ->disabled(function (string $operation) {
                            return $operation === 'edit' && $this->ownerRecord->type === PlanType::SEAT_BASED->value;
                        })
                        ->dehydrated()
                        ->live()
                        ->required(),
                    Select::make('currency_id')
                        ->label('Currency')
                        ->live()
                        ->options(
                            $this->currencyService->getAllCurrencies()
                                ->mapWithKeys(function ($currency) {
                                    return [$currency->id => $currency->name.' ('.$currency->symbol.')'];
                                })
                                ->toArray()
                        )
                        ->default($defaultCurrency)
                        ->required()
                        ->unique(modifyRuleUsing: function (Unique $rule, Get $get, RelationManager $livewire) {
                            return $rule->where('plan_id', $livewire->ownerRecord->id)->ignore($get('id'));
                        })
                        ->preload(),
                    TextInput::make('price')
                        ->required()
                        ->type('number')
                        ->gte(0)
                        ->live()
                        ->label(function (Get $get) {
                            if ($get('type') === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                                return __('Base Price');
                            }

                            return $get('type') === PlanPriceType::FLAT_RATE->value ? __('Price') : __('Fixed Fee');
                        })
                        ->helperText(
                            function (Get $get) {
                                if ($get('type') === PlanPriceType::FLAT_RATE->value || $get('type') === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
                                    return new HtmlString(
                                        __('Enter price in lowest denomination for a currency (cents). E.g. 1000 = $10.00')
                                    );
                                }

                                return new HtmlString(
                                    '<strong>'.__('Important: Fixed fee is available only for Stripe & Polar.').'</strong>'
                                    .'<br/><br/>'.__('A fixed fee is an amount that your customer will be charged every billing cycle in addition to any usage-based amount. Enter fixed fee in lowest denomination for a currency (cents). E.g. 1000 = $10.00')
                                    .'<br/><br/>'.__('It is highly recommended that you set up a fixed fee for your usage-based billing plans if you are dealing with low-trust customers, as customers can keep using your service and then disable their credit card to avoid being charged for usage.')
                                );
                            }
                        ),
                    TextInput::make('included_seats')
                        ->type('number')
                        ->required()
                        ->minValue(1)
                        ->label(__('Included Seats'))
                        ->helperText(function (string $operation) {
                            $text = __('Number of seats included in the base price.');

                            if ($operation === 'edit') {
                                $text .= ' '.__('Warning: Changing this value will not update existing subscriptions on the payment provider. You may need to manually adjust active subscriptions.');
                            }

                            return $text;
                        })
                        ->visible(function (Get $get) {
                            return $get('type') === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value;
                        }),
                    TextInput::make('extra_seat_price')
                        ->type('number')
                        ->required()
                        ->gte(0)
                        ->label(__('Price per Extra Seat'))
                        ->helperText(__('Cost per additional seat beyond included seats (in cents).'))
                        ->visible(function (Get $get) {
                            return $get('type') === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value;
                        }),
                    TextInput::make('setup_fee')
                        ->type('number')
                        ->gte(0)
                        ->label(__('Setup Fee'))
                        ->helperText(
                            new HtmlString(
                                __('One-time fee charged on first subscription only. Enter in lowest denomination (cents). E.g. 500 = $5.00. Leave empty for no setup fee. Note: not all payment providers support setup fees.')
                            )
                        ),
                    TextInput::make('price_per_unit')
                        ->required()
                        ->type('number')
                        ->gte(0)
                        ->label(__('Price per unit'))
                        ->visible(function ($get) {
                            return $get('type') === PlanPriceType::USAGE_BASED_PER_UNIT->value;
                        })
                        ->helperText(__('Enter price per unit in lowest denomination for a currency (cents). E.g. 1000 = $10.00')),
                    Repeater::make('tiers')
                        ->helperText(__('Enter tier prices in lowest denomination for a currency (cents). E.g. 1000 = $10.00'))
                        ->label(__('Tiers'))
                        ->schema([
                            TextInput::make('until_unit')->label(__('Up until (x) units'))->required()
                                ->suffixAction(Action::make('infinity')->icon('icon-infinity')->action(function (Get $get, Set $set) {
                                    $set('until_unit', '∞');
                                })),
                            TextInput::make('per_unit')->label(__('Price per unit'))->numeric()->minValue(0)->default(0)->required(),
                            TextInput::make('flat_fee')->label(__('Flat fee'))->numeric()->minValue(0)->default(0)->required(),
                        ])
                        ->live()
                        ->default([
                            [
                                PlanPriceTierConstants::UNTIL_UNIT => 5,
                                PlanPriceTierConstants::PER_UNIT => 0,
                                PlanPriceTierConstants::FLAT_FEE => 0,
                            ],
                            [
                                PlanPriceTierConstants::UNTIL_UNIT => '∞',
                                PlanPriceTierConstants::PER_UNIT => 0,
                                PlanPriceTierConstants::FLAT_FEE => 0,
                            ],
                        ])
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                if (! is_array($value) || empty($value)) {
                                    $fail(__('At least one tier is required'));

                                    return;
                                }

                                if (last($value)[PlanPriceTierConstants::UNTIL_UNIT] !== '∞') {
                                    $fail(__('The last tier must have "∞" as the value for "Up until (x) units"'));
                                }

                                // Up until (x) units values should be in ascending order

                                $current = 0;
                                foreach ($value as $tier) {
                                    if ($tier[PlanPriceTierConstants::UNTIL_UNIT] === '∞') {
                                        break;
                                    }

                                    if ($tier[PlanPriceTierConstants::UNTIL_UNIT] <= $current) {
                                        $fail(__('The "Up until (x) units" values should be in ascending order'));
                                    }

                                    $current = $tier[PlanPriceTierConstants::UNTIL_UNIT];
                                }

                                foreach ($value as $tier) {
                                    if (! is_numeric($tier[PlanPriceTierConstants::UNTIL_UNIT]) && $tier[PlanPriceTierConstants::UNTIL_UNIT] !== '∞') {
                                        $fail(__('The "Up until (x) units" values should be an integer or "∞"'));
                                    }
                                }

                                // only one infinite tier is allowed
                                $infiniteTiers = collect($value)->filter(function ($tier) {
                                    return $tier[PlanPriceTierConstants::UNTIL_UNIT] === '∞';
                                });

                                if ($infiniteTiers->count() > 1) {
                                    $fail(__('Only one tier can have "∞" as the value for "Up until (x) units"'));
                                }
                            },
                        ])
                        ->visible(function ($get) {
                            return $get('type') === PlanPriceType::USAGE_BASED_TIERED_VOLUME->value ||
                                $get('type') === PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value;
                        })
                        ->columns(3),
                    Section::make([
                        TextInput::make('example_unit_quantity')
                            ->integer()
                            ->label(__('Unit Quantity'))
                            ->helperText(__('Enter an example unit quantity to see how the price is calculated.'))
                            ->dehydrated(false)
                            ->live(),
                        Placeholder::make('price_preview')
                            ->label(__('Price Preview Calculation'))
                            ->content(function (Get $get) {
                                return $this->calculatePricePreview($get);
                            })
                            ->visible(function ($get) {
                                return $get('type') === PlanPriceType::USAGE_BASED_TIERED_VOLUME->value ||
                                    $get('type') === PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value;
                            }),
                    ])->visible(function ($get) {
                        return $get('type') === PlanPriceType::USAGE_BASED_TIERED_VOLUME->value ||
                            $get('type') === PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value;
                    })->heading(__('Price Preview'))
                        ->columns(2),
                ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('setup_fee')
                    ->label(__('Setup Fee'))
                    ->formatStateUsing(function (?string $state, $record) {
                        if (! $state || $state == 0) {
                            return '-';
                        }

                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('currency.name')
                    ->label(__('Currency')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    private function calculatePricePreview(Get $get): HtmlString
    {
        try {

            $type = $get('type');
            $currency = Currency::find($get('currency_id'));

            if ($type === PlanPriceType::USAGE_BASED_TIERED_VOLUME->value) {
                $explanation = '';

                if (! empty($get('example_unit_quantity'))) {
                    // get the tier where the example_unit_quantity falls
                    $tier = collect($get('tiers'))->first(function ($tier) use ($get) {
                        return $tier[PlanPriceTierConstants::UNTIL_UNIT] === '∞' || $tier[PlanPriceTierConstants::UNTIL_UNIT] >= $get('example_unit_quantity');
                    });

                    $price = $tier[PlanPriceTierConstants::FLAT_FEE] + ($tier[PlanPriceTierConstants::PER_UNIT] * $get('example_unit_quantity'));

                    $priceFormatted = money($price, $currency->code);

                    $explanation = '('.$get('example_unit_quantity').' * '.money($tier[PlanPriceTierConstants::PER_UNIT], $currency->code).') + '.money($tier[PlanPriceTierConstants::FLAT_FEE], $currency->code).' = '.$priceFormatted;

                    if ($get('price') > 0) {
                        $fixedFee = $get('price');
                        $priceFormatted = money($fixedFee, $currency->code);

                        if (! empty($explanation)) {
                            $explanation .= '<br/> + ';
                        }

                        $explanation .= $priceFormatted.__(' (Fixed fee)');

                        $explanation .= ' = '.money($price + $fixedFee, $currency->code);
                    }
                }

                return new HtmlString(
                    __('Tiered (Volume) pricing model is calculated as follows: <br/> (volume of usage * price per unit) + Flat fee of that tier + Fixed fee (if any).')
                    .'<br/><br/>'
                    .$explanation
                );
            }

            if ($type === PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value) {
                $explanation = '';

                if (! empty($get('example_unit_quantity'))) {
                    $tiers = collect($get('tiers'));

                    $price = 0;
                    $priceFormatted = money($price, $currency->code);
                    $remaining = $get('example_unit_quantity');
                    $explanation = '';
                    $lastTier = null;

                    foreach ($tiers as $tier) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $maxUnitsInCurrentTier = ($tier[PlanPriceTierConstants::UNTIL_UNIT] === '∞' ? 100000000000 : $tier[PlanPriceTierConstants::UNTIL_UNIT]) - ($lastTier ? $lastTier[PlanPriceTierConstants::UNTIL_UNIT] : 0);
                        $unitsCalculatedAtThisTier = min($remaining, $maxUnitsInCurrentTier);

                        $tierPrice = $tier[PlanPriceTierConstants::FLAT_FEE] + ($tier[PlanPriceTierConstants::PER_UNIT] * $unitsCalculatedAtThisTier);

                        $price += $tierPrice;
                        $priceFormatted = money($price, $currency->code);

                        if (! empty($explanation)) {
                            $explanation .= '<br/> + ';
                        }

                        $explanation .= '(('.$unitsCalculatedAtThisTier.' * '.money($tier[PlanPriceTierConstants::PER_UNIT], $currency->code).') + '.money($tier[PlanPriceTierConstants::FLAT_FEE], $currency->code).')';

                        $remaining -= $unitsCalculatedAtThisTier;
                        $lastTier = $tier;
                    }

                    $explanation .= ' = '.$priceFormatted;

                    if ($get('price') > 0) {
                        $fixedFee = $get('price');
                        $priceFormatted = money($fixedFee, $currency->code);

                        if (! empty($explanation)) {
                            $explanation .= '<br/> + ';
                        }

                        $explanation .= $priceFormatted.__(' (Fixed fee)');

                        $explanation .= ' = '.money($price + $fixedFee, $currency->code);
                    }
                }

                return new HtmlString(
                    __('Tiered (Graduated) pricing model is calculated as follows: <br/> Sum of ((volume of usage * price per unit) + Flat fee) at each tier + Fixed fee (if any).')
                    .'<br/><br/>'
                    .$explanation
                );
            }
        } catch (Throwable $exception) {

        }

        return new HtmlString('');
    }
}
