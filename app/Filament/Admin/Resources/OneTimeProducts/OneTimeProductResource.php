<?php

namespace App\Filament\Admin\Resources\OneTimeProducts;

use App\Filament\Admin\Resources\OneTimeProducts\Pages\CreateOneTimeProduct;
use App\Filament\Admin\Resources\OneTimeProducts\Pages\EditOneTimeProduct;
use App\Filament\Admin\Resources\OneTimeProducts\Pages\ListOneTimeProducts;
use App\Filament\Admin\Resources\OneTimeProducts\RelationManagers\PaymentProviderDataRelationManager;
use App\Filament\Admin\Resources\OneTimeProducts\RelationManagers\PricesRelationManager;
use App\Models\OneTimeProduct;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OneTimeProductResource extends Resource
{
    protected static ?string $model = OneTimeProduct::class;

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
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                // add a random string if there is a product with the same slug
                                $state = Str::slug($get('name'));
                                if (OneTimeProduct::where('slug', $state)->exists()) {
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
                    TextInput::make('max_quantity')
                        ->label(__('Max Quantity'))
                        ->type('number')
                        ->required()
                        ->default(1)
                        ->minValue(0)
                        ->helperText(__('The maximum quantity of this product that can be purchased at once. Set to 0 for unlimited quantity. If set to 1, customers will not be able to edit the quantity on the checkout page.')),
                    Toggle::make('is_active')
                        ->helperText(__('If the product is not active, your customers will not be able to purchase it.'))
                        ->default(true)
                        ->label(__('Active')),
                    Toggle::make('is_visible')
                        ->label(__('Is Visible'))
                        ->default(true)
                        ->helperText('If true, then this product will be visible in the components that show the products on the frontend. If this is disabled, this product will be hidden in the components that show the products on the frontend, but users who have the product URL will still be able to purchase it.')
                        ->required(),
                    KeyValue::make('metadata')
                        ->label(__('Metadata'))
                        ->helperText(__('Add any additional data to this product. You can use this to store product features that could later be retrieved to serve your users.'))
                        ->keyLabel(__('Property name'))
                        ->valueLabel(__('Property value')),
                    Repeater::make('features')
                        ->label(__('Features'))
                        ->helperText(__('Add features that this product offers. These will be displayed on the checkout page.'))
                        ->schema([
                            TextInput::make('feature')->required()->label(__('Feature')),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(__('A one-time purchase product is a non-recurring product that is purchased once for a certain price.'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => ListOneTimeProducts::route('/'),
            'create' => CreateOneTimeProduct::route('/create'),
            'edit' => EditOneTimeProduct::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('One-time Purchase Product');
    }

    public static function getNavigationLabel(): string
    {
        return __('One-time Purchase Products');
    }

    public static function getPluralModelLabel(): string
    {
        return __('One-time Purchase Products');
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
            PaymentProviderDataRelationManager::class,
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return $record->isDeletable();
    }
}
