<?php

namespace App\Livewire\Filament;

use App\Services\ConfigService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class LemonSqueezySettings extends Component implements HasForms
{
    private ConfigService $configService;

    use InteractsWithForms;

    public ?array $data = [];

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function render()
    {
        return view('livewire.filament.lemon-squeezy-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'api_key' => $this->configService->get('services.lemon-squeezy.api_key'),
            'store_id' => $this->configService->get('services.lemon-squeezy.store_id'),
            'signing_secret' => $this->configService->get('services.lemon-squeezy.signing_secret'),
            'is_test_mode' => $this->configService->get('services.lemon-squeezy.is_test_mode'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('api_key')
                            ->label(__('API Key')),
                        TextInput::make('store_id')
                            ->label(__('Store ID')),
                        TextInput::make('signing_secret')
                            ->label(__('Signing Secret')),
                        Toggle::make('is_test_mode')
                            ->label(__('Is Test Mode'))
                            ->default(false)
                            ->helperText(__('Check this box if you are using Lemon Squeezy in test mode.')),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Lemon Squeezy Settings'))
                        ->view('filament.admin.resources.payment-provider-resource.pages.partials.lemon-squeezy-how-to'),
                ])->columnSpan([
                    'sm' => 6,
                    'xl' => 4,
                    '2xl' => 4,
                ]),
            ])->columns(12)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('services.lemon-squeezy.api_key', $data['api_key']);
        $this->configService->set('services.lemon-squeezy.store_id', $data['store_id']);
        $this->configService->set('services.lemon-squeezy.signing_secret', $data['signing_secret']);
        $this->configService->set('services.lemon-squeezy.is_test_mode', $data['is_test_mode']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
