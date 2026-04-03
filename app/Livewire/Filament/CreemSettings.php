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

class CreemSettings extends Component implements HasForms
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
        return view('livewire.filament.creem-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'api_key' => $this->configService->get('services.creem.api_key'),
            'webhook_secret' => $this->configService->get('services.creem.webhook_secret'),
            'is_test_mode' => $this->configService->get('services.creem.is_test_mode'),
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
                        TextInput::make('webhook_secret')
                            ->label(__('Webhook Secret')),
                        Toggle::make('is_test_mode')
                            ->label(__('Is Test Mode'))
                            ->default(false)
                            ->helperText(__('Check this box if you are using Creem in test mode. Test mode uses a different API URL.')),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Creem Settings'))
                        ->view('filament.admin.resources.payment-provider-resource.pages.partials.creem-how-to'),
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

        $this->configService->set('services.creem.api_key', $data['api_key']);
        $this->configService->set('services.creem.webhook_secret', $data['webhook_secret']);
        $this->configService->set('services.creem.is_test_mode', $data['is_test_mode']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
