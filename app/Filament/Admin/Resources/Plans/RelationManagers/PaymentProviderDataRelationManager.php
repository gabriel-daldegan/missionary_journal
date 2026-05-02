<?php

namespace App\Filament\Admin\Resources\Plans\RelationManagers;

use App\Constants\PaymentProviderConstants;
use App\Models\PaymentProvider;
use App\Services\PaymentProviders\Creem\CreemProductValidator;
use App\Services\PaymentProviders\LemonSqueezy\LemonSqueezyProductValidator;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PaymentProviderDataRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentProviderData';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): ?string
    {
        return __('Payment Provider Data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('payment_provider_id')
                    ->label('Payment Provider')
                    ->options(
                        PaymentProvider::where('slug', '!=', PaymentProviderConstants::OFFLINE_SLUG)
                            ->get()
                            ->mapWithKeys(function ($paymentProvider) {
                                return [$paymentProvider->id => $paymentProvider->name];
                            })
                            ->toArray()
                    )
                    ->default(PaymentProvider::where('slug', PaymentProviderConstants::LEMON_SQUEEZY_SLUG)?->first()?->id ?? null)
                    ->live()
                    ->unique(modifyRuleUsing: function ($rule, Get $get, RelationManager $livewire) {
                        return $rule->where('plan_id', $livewire->ownerRecord->id)->ignore($get('id'));
                    })
                    ->live()
                    ->preload()
                    ->required(),
                TextInput::make('payment_provider_product_id')
                    ->label('Payment Provider Product/Variant ID')
                    ->helperText('For Lemon Squeezy, this should be equal to the variant ID.')
                    ->required()
                    ->maxLength(255),
                Actions::make([
                    Action::make('submit')
                        ->label(__('Validate Product (Lemon Squeezy)'))
                        ->color('success')
                        ->outlined()
                        ->visible(fn ($get) => $get('payment_provider_id') == PaymentProvider::where('slug', PaymentProviderConstants::LEMON_SQUEEZY_SLUG)?->first()?->id)
                        ->action(function (LemonSqueezyProductValidator $validator, $get) {
                            if ($get('payment_provider_id') != PaymentProvider::where('slug', PaymentProviderConstants::LEMON_SQUEEZY_SLUG)?->first()?->id) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('Invalid Payment Provider'))
                                    ->body(__('The selected payment provider is not Lemon Squeezy.'))
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $variantId = $get('payment_provider_product_id');

                            try {
                                $validator->validatePlan($variantId, $this->ownerRecord);
                            } catch (Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('Problem validating product'))
                                    ->body(__($e->getMessage()))
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title(__('Product found'))
                                ->body(__('The product with the variant ID :variantId was found and is matching your plan product details.', ['variantId' => $variantId]))
                                ->persistent()
                                ->send();
                        }),
                    Action::make('validate_creem')
                        ->label(__('Validate Product (Creem)'))
                        ->color('success')
                        ->outlined()
                        ->visible(fn ($get) => $get('payment_provider_id') == PaymentProvider::where('slug', PaymentProviderConstants::CREEM_SLUG)?->first()?->id)
                        ->action(function (CreemProductValidator $validator, $get) {
                            if ($get('payment_provider_id') != PaymentProvider::where('slug', PaymentProviderConstants::CREEM_SLUG)?->first()?->id) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('Invalid Payment Provider'))
                                    ->body(__('The selected payment provider is not Creem.'))
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $productId = $get('payment_provider_product_id');

                            try {
                                $validator->validatePlan($productId, $this->ownerRecord);
                            } catch (Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('Problem validating product'))
                                    ->body(__($e->getMessage()))
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title(__('Product found'))
                                ->body(__('The product with the ID :productId was found and is matching your plan product details.', ['productId' => $productId]))
                                ->persistent()
                                ->send();
                        }),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(new HtmlString('⚠️ Advanced settings, these records are created automatically when a plan is created. You <b>SHOULD NOT</b> need to create or edit these records manually unless you use "Lemon Squeezy" or "Creem" as your payment provider because they do not support plan creation via the API.'))
            ->recordTitleAttribute('Payment Provider Product/Variant ID')
            ->columns([
                TextColumn::make('payment_provider_id')
                    ->label('Payment Provider')->formatStateUsing(function ($record) {
                        return $record->paymentProvider->name;
                    }),
                TextColumn::make('payment_provider_product_id')
                    ->label('Payment Provider Product/Variant ID'),
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
