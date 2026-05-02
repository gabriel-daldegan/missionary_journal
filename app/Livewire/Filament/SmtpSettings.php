<?php

namespace App\Livewire\Filament;

use App\Services\ConfigService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class SmtpSettings extends Component implements HasForms
{
    private ConfigService $configService;

    protected string $slug = 'smtp';

    use InteractsWithForms;

    public ?array $data = [];

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function render()
    {
        return view('livewire.filament.smtp-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'host' => $this->configService->get('mail.mailers.smtp.host'),
            'port' => $this->configService->get('mail.mailers.smtp.port'),
            'username' => $this->configService->get('mail.mailers.smtp.username'),
            'password' => $this->configService->get('mail.mailers.smtp.password'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('host')
                            ->label(__('Host')),
                        TextInput::make('port')
                            ->label(__('Port')),
                        TextInput::make('username')
                            ->label(__('Username')),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('mail.mailers.smtp.host', $data['host']);
        $this->configService->set('mail.mailers.smtp.port', $data['port']);
        $this->configService->set('mail.mailers.smtp.username', $data['username']);
        $this->configService->set('mail.mailers.smtp.password', $data['password']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
