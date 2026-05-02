<?php

namespace App\Livewire\Filament;

use App\Services\ConfigService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class InvoiceSettings extends Component implements HasForms
{
    private ConfigService $configService;

    use InteractsWithForms;

    public ?array $data = [];

    public function render()
    {
        return view('livewire.filament.invoice-settings');
    }

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function mount(): void
    {
        $this->form->fill([
            'invoices_enabled' => $this->configService->get('invoices.enabled', false),
            'serial_number_series' => $this->configService->get('invoices.serial_number.series', 'INV'),
            'seller_name' => $this->configService->get('invoices.seller.attributes.name'),
            'seller_address' => $this->configService->get('invoices.seller.attributes.address'),
            'seller_code' => $this->configService->get('invoices.seller.attributes.code'),
            'seller_tax_number' => $this->configService->get('invoices.seller.attributes.vat'),
            'seller_phone' => $this->configService->get('invoices.seller.attributes.phone'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Invoice generation'))
                    ->schema([
                        Toggle::make('invoices_enabled')
                            ->label(__('Enable invoice generation'))
                            ->helperText(__('If enabled, invoices will be generated for each successful transaction. Customers will be able to see their invoices in their dashboard.'))
                            ->required(),
                        TextInput::make('serial_number_series')
                            ->required()
                            ->default('')
                            ->label(__('Invoice number prefix')),
                        TextInput::make('seller_name')
                            ->default('')
                            ->label(__('Company name')),
                        TextInput::make('seller_code')
                            ->default('')
                            ->label(__('Company code')),
                        TextInput::make('seller_address')
                            ->default('')
                            ->label(__('Company address')),
                        TextInput::make('seller_tax_number')
                            ->default('')
                            ->label(__('Company tax number (VAT)')),
                        TextInput::make('seller_phone')
                            ->default('')
                            ->label(__('Company phone')),
                        Actions::make([
                            Action::make('preview')
                                ->label(__('Generate Preview'))
                                ->icon('heroicon-o-eye')
                                ->color('gray')
                                ->modalSubmitAction(false)
                                ->modalCancelAction(false)
                                ->url(function ($get) {
                                    $url = route('invoice.preview', [
                                        'serial_number_series' => $get('serial_number_series'),
                                        'seller_name' => $get('seller_name'),
                                        'seller_code' => $get('seller_code'),
                                        'seller_address' => $get('seller_address'),
                                        'seller_tax_number' => $get('seller_tax_number'),
                                        'seller_phone' => $get('seller_phone'),
                                    ]);

                                    return $url;
                                }),
                        ]),
                    ]),

            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('invoices.enabled', $data['invoices_enabled']);
        $this->configService->set('invoices.serial_number.series', $data['serial_number_series']);
        $this->configService->set('invoices.seller.attributes.name', $data['seller_name']);
        $this->configService->set('invoices.seller.attributes.address', $data['seller_address']);
        $this->configService->set('invoices.seller.attributes.code', $data['seller_code']);
        $this->configService->set('invoices.seller.attributes.vat', $data['seller_tax_number']);
        $this->configService->set('invoices.seller.attributes.phone', $data['seller_phone']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
