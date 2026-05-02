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

class PolarSettings extends Component implements HasForms
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
        return view('livewire.filament.polar-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'access_token' => $this->configService->get('services.polar.access_token'),
            'webhook_secret' => $this->configService->get('services.polar.webhook_secret'),
            'is_sandbox' => $this->configService->get('services.polar.is_sandbox'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('access_token')
                            ->label(__('Access Token')),
                        TextInput::make('webhook_secret')
                            ->label(__('Webhook Secret')),
                        Toggle::make('is_sandbox')
                            ->label(__('Sandbox Mode'))
                            ->default(false)
                            ->helperText(__('Enable this if you are using the Polar sandbox environment. Sandbox mode uses a different API URL.')),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Polar Settings'))
                        ->view('filament.admin.resources.payment-provider-resource.pages.partials.polar-how-to'),
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

        $this->configService->set('services.polar.access_token', $data['access_token']);
        $this->configService->set('services.polar.webhook_secret', $data['webhook_secret']);
        $this->configService->set('services.polar.is_sandbox', $data['is_sandbox']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
