<?php

namespace App\Filament\Admin\Resources\OneTimeProducts\RelationManagers;

use App\Services\CurrencyService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

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
                    TextInput::make('price')
                        ->required()
                        ->type('number')
                        ->gte(0)
                        ->helperText(__('Enter price in lowest denomination for a currency (cents). E.g. 1000 = 10.00')),
                    Select::make('currency_id')
                        ->label('Currency')
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
                            return $rule->where('one_time_product_id', $livewire->ownerRecord->id)->ignore($get('id'));
                        })
                        ->preload(),

                ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('price')
                    // divide by 100 to get price in dollars
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('currency.name')
                    ->label('Currency'),
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
            ])->modelLabel(__('Price'));
    }
}
