<?php

namespace App\Livewire\Filament;

use App\Services\ConfigService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class TwilioSettings extends Component implements HasForms
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
        return view('livewire.filament.twilio-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'sid' => $this->configService->get('services.twilio.sid'),
            'token' => $this->configService->get('services.twilio.token'),
            'from' => $this->configService->get('services.twilio.from'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('sid')
                            ->label(__('SID'))
                            ->helperText(__('The Account SID from your Twilio account.'))
                            ->required(),
                        TextInput::make('token')
                            ->label(__('Token'))
                            ->helperText(__('The Auth Token from your Twilio account.'))
                            ->required(),
                        TextInput::make('from')
                            ->label(__('From'))
                            ->helperText(__('The phone number or alphanumeric sender ID to send messages from.'))
                            ->required(),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Paddle Settings'))
                        ->view('filament.admin.resources.verification-provider-resource.pages.partials.twilio-how-to'),
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

        $this->configService->set('services.twilio.sid', $data['sid']);
        $this->configService->set('services.twilio.token', $data['token']);
        $this->configService->set('services.twilio.from', $data['from']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
