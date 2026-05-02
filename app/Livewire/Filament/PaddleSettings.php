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

class PaddleSettings extends Component implements HasForms
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
        return view('livewire.filament.paddle-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'vendor_id' => $this->configService->get('services.paddle.vendor_id'),
            'client_side_token' => $this->configService->get('services.paddle.client_side_token'),
            'vendor_auth_code' => $this->configService->get('services.paddle.vendor_auth_code'),
            'webhook_secret' => $this->configService->get('services.paddle.webhook_secret'),
            'is_sandbox' => $this->configService->get('services.paddle.is_sandbox'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([

                        TextInput::make('vendor_id')
                            ->label(__('Vendor ID')),
                        TextInput::make('client_side_token')
                            ->label(__('Client Side Token')),
                        TextInput::make('vendor_auth_code')
                            ->label(__('Vendor Auth Code')),
                        TextInput::make('webhook_secret')
                            ->label(__('Webhook Secret')),
                        Toggle::make('is_sandbox')
                            ->label(__('Is Sandbox'))
                            ->required(),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Paddle Settings'))
                        ->view('filament.admin.resources.payment-provider-resource.pages.partials.paddle-how-to'),
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

        $this->configService->set('services.paddle.vendor_id', $data['vendor_id']);
        $this->configService->set('services.paddle.client_side_token', $data['client_side_token']);
        $this->configService->set('services.paddle.vendor_auth_code', $data['vendor_auth_code']);
        $this->configService->set('services.paddle.webhook_secret', $data['webhook_secret']);
        $this->configService->set('services.paddle.is_sandbox', $data['is_sandbox']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
