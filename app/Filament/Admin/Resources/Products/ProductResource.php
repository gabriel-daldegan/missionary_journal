<?php

namespace App\Filament\Admin\Resources\Products;

use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Product Management');
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
                        ->label(__('Slug'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                // add a random string if there is a product with the same slug
                                $state = Str::slug($get('name'));
                                if (Product::where('slug', $state)->exists()) {
                                    $state .= '-'.Str::random(5);
                                }

                                return Str::slug($state);
                            }

                            return $state;
                        })
                        ->helperText(__('Leave empty to generate slug automatically from product name.'))
                        ->maxLength(255)
                        ->rules(['alpha_dash'])
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    Textarea::make('description')
                        ->label(__('Description'))
                        ->helperText(__('One line description of the product.')),
                    Toggle::make('is_popular')
                        ->label(__('Popular product'))
                        ->helperText(__('Mark this product as popular. This will be used to highlight this product in the pricing page.')),
                    Toggle::make('is_default')
                        ->label(__('Is default product'))
                        ->validationAttribute(__('default product'))
                        ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                            return $rule->where('is_default', true);
                        })
                        ->default(false)
                        ->helperText(__('A default product is a kind of a hidden product that allows you to set the features (and metadata) for users that have no active plan. Add a default product if you want to offer a free tier to your users. You can only have 1 default product and it cannot have any plans.')),
                    KeyValue::make('metadata')
                        ->label(__('Metadata'))
                        ->helperText(__('Add any additional data to this product. You can use this to store product features that could later be retrieved to serve your users.'))
                        ->keyLabel(__('Property name'))
                        ->valueLabel(__('Property value')),
                    Repeater::make('features')
                        ->label(__('Features'))
                        ->helperText(__('Add features that this plan offers. These will be displayed on the pricing page and on the checkout page.'))
                        ->schema([
                            TextInput::make('feature')->required()->label(__('Feature')),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(__('A product is bundle of features that you offer to your customers.'))
            ->description(__('If you want to provide a Starter, Pro and Premium offerings to your customers, create a product for each of them.'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label(__('Name')),
                TextColumn::make('slug')->searchable()->sortable()->label(__('Slug')),
                IconColumn::make('is_popular')->label(__('Popular'))->boolean(),
                IconColumn::make('is_default')->label(__('Default'))->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('Subscription Product');
    }

    public static function getNavigationLabel(): string
    {
        return __('Subscription Products');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Subscription Products');
    }
}
