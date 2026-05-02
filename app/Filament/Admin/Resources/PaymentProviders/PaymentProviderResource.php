<?php

namespace App\Filament\Admin\Resources\PaymentProviders;

use App\Filament\Admin\Resources\PaymentProviders\Pages\CreemSettings;
use App\Filament\Admin\Resources\PaymentProviders\Pages\EditPaymentProvider;
use App\Filament\Admin\Resources\PaymentProviders\Pages\LemonSqueezySettings;
use App\Filament\Admin\Resources\PaymentProviders\Pages\ListPaymentProviders;
use App\Filament\Admin\Resources\PaymentProviders\Pages\PaddleSettings;
use App\Filament\Admin\Resources\PaymentProviders\Pages\PolarSettings;
use App\Filament\Admin\Resources\PaymentProviders\Pages\StripeSettings;
use App\Models\PaymentProvider;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PaymentProviderResource extends Resource
{
    protected static ?string $model = PaymentProvider::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Payment Providers');
    }

    public static function getModelLabel(): string
    {
        return __('Payment Provider');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->helperText(__('The name of the payment provider (shown on checkout page).'))
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label(__('Active'))
                        ->helperText(__('Deactivating this payment provider will prevent it from being used for new & old subscriptions. Customers will not be able to pay for their services so USE WITH CAUTION.'))
                        ->required(),
                    Toggle::make('is_enabled_for_new_payments')
                        ->label(__('Enabled for new payments'))
                        ->helperText(__('If disabled, this payment provider will not be shown on the checkout page, but will still be available for existing subscriptions and receiving webhooks.'))
                        ->required(),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->columns([
                TextColumn::make('icon')
                    ->label(__('Icon'))
                    ->getStateUsing(function (PaymentProvider $record) {
                        return new HtmlString(
                            '<div class="flex gap-2">'.
                            ' <img src="'.asset('images/payment-providers/'.$record->slug.'.png').'" alt="'.$record->name.'" class="h-6"> '
                            .'</div>'
                        );
                    }),
                TextColumn::make('name')->label(__('Name')),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->label(__('Active')),
                ToggleColumn::make('is_enabled_for_new_payments')
                    ->label(__('Enabled for new payments')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
            ])
            ->defaultSort('sort', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentProviders::route('/'),
            'edit' => EditPaymentProvider::route('/{record}/edit'),
            'stripe-settings' => StripeSettings::route('/stripe-settings'),
            'paddle-settings' => PaddleSettings::route('/paddle-settings'),
            'lemon-squeezy-settings' => LemonSqueezySettings::route('/lemon-squeezy-settings'),
            'creem-settings' => CreemSettings::route('/creem-settings'),
            'polar-settings' => PolarSettings::route('/polar-settings'),
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

    public static function getNavigationLabel(): string
    {
        return __('Payment Providers');
    }
}
