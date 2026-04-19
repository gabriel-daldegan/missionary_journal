<?php

namespace App\Filament\Admin\Resources\Discounts;

use App\Constants\DiscountConstants;
use App\Filament\Admin\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Admin\Resources\Discounts\RelationManagers\CodesRelationManager;
use App\Models\Discount;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Product Management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Discounts');
    }

    public static function getModelLabel(): string
    {
        return __('Discount');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // card
                Section::make([
                    TextInput::make('name')
                        ->required()
                        ->label(__('Name'))
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label(__('Description'))
                        ->maxLength(255),

                    Radio::make('type')
                        ->required()
                        ->label(__('Type'))
                        ->options([
                            DiscountConstants::TYPE_FIXED => __('Fixed amount'),
                            DiscountConstants::TYPE_PERCENTAGE => __('Percentage (of the total price)'),
                        ])
                        ->default('fixed'),

                    Grid::make()->schema([
                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->helperText(__('If you choose percentage, enter a number between 0 and 100. For example: 90 for 90%. For fixed amount, enter the amount in cents. For example: 1000 for $10.00'))
                            ->integer()
                            ->required(),
                        DateTimePicker::make('valid_until'),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('is_enabled_for_all_plans')
                            ->label(__('Enabled for all plans'))
                            ->helperText(__('If enabled, this discount will be applied to all plans. If disabled, you can select specific plans.'))
                            ->live(),
                        Select::make('plans')
                            ->multiple()
                            ->label(__('Plans'))
                            ->disabled(function (Get $get) {
                                return $get('is_enabled_for_all_plans') === true;
                            })
                            ->relationship('plans', 'name', modifyQueryUsing: function (Builder $query) {
                                return $query->select('plans.id', 'plans.name')->distinct();
                            })
                            ->preload()
                            ->helperText(__('Select the plans that this discount will be applied to.')),
                        Toggle::make('is_enabled_for_all_one_time_products')
                            ->label(__('Enabled for all one-time products'))
                            ->helperText(__('If enabled, this discount will be applied to all one-time products. If disabled, you can select specific one-time products.'))
                            ->live(),
                        Select::make('oneTimeProducts')
                            ->label(__('One-time purchase products'))
                            ->multiple()
                            ->relationship('oneTimeProducts', 'name', modifyQueryUsing: function (Builder $query) {
                                return $query->select('one_time_products.id', 'one_time_products.name')->distinct();
                            })
                            ->disabled(function (Get $get) {
                                return $get('is_enabled_for_all_one_time_products') === true;
                            })
                            ->preload()
                            ->helperText(__('Select the one-time products that this discount will be applied to.')),
                    ]),
                    //                    Forms\Components\Select::make('action_type')  // TODO: implement this in the future
                    //                        ->options(DiscountConstants::ACTION_TYPES)
                    //                        // change the default value to null
                    //                        ->default(null),
                    TextInput::make('max_redemptions')
                        ->label(__('Maximum Redemptions'))
                        ->integer()
                        ->default(-1)
                        ->helperText(__('Enter -1 for unlimited redemptions (total).')),
                    TextInput::make('max_redemptions_per_user')
                        ->label(__('Maximum Redemptions Per User'))
                        ->integer()
                        ->default(-1)
                        ->helperText(__('Enter -1 for unlimited redemptions per user.')),
                    Toggle::make('is_recurring')
                        ->label(__('Is Recurring?'))
                        ->helperText(__('If enabled, this discount will keep being applied to the subscription forever (or until valid if you set maximum valid date).'))
                        ->required(),
                    Toggle::make('is_active')
                        ->label(__('Active'))
                        ->default(true)
                        ->required(),
                    TextInput::make('duration_in_months')
                        ->label(__('Duration in Months'))
                        ->integer()
                        ->helperText(__('This allows you to define how many months the discount should apply. Only works with payment providers that support this feature. (like Stripe, Lemon Squeezy, Polar & Creem)'))
                        ->default(null),
                    TextInput::make('maximum_recurring_intervals')
                        ->label(__('Maximum Recurring Intervals'))
                        ->integer()
                        ->helperText(__('Amount of subscription billing periods that this discount recurs for. Only works with payment providers that support this feature. (like Paddle)'))
                        ->default(null),

                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')->label(__('Type')),
                TextColumn::make('amount')->label(__('Amount'))->formatStateUsing(function (string $state, $record) {
                    if ($record->type === DiscountConstants::TYPE_PERCENTAGE) {
                        return $state.'%';
                    }

                    return intval($state) / 100;
                }),
                ToggleColumn::make('is_active')->label(__('Active')),
                TextColumn::make('redemptions')->label(__('Redemptions')),
                TextColumn::make('updated_at')->label(__('Updated at'))
                    ->dateTime(config('app.datetime_format')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Discounts');
    }
}
