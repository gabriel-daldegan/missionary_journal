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

class MailgunSettings extends Component implements HasForms
{
    private ConfigService $configService;

    protected string $slug = 'mailgun';

    use InteractsWithForms;

    public ?array $data = [];

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function render()
    {
        return view('livewire.filament.amazon-ses-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'domain' => $this->configService->get('services.'.$this->slug.'.domain'),
            'secret' => $this->configService->get('services.'.$this->slug.'.secret'),
            'endpoint' => $this->configService->get('services.'.$this->slug.'.endpoint'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('domain')
                            ->label(__('Domain'))
                            ->required(),
                        TextInput::make('secret')
                            ->label(__('Secret'))
                            ->password()
                            ->required(),
                        TextInput::make('endpoint')
                            ->label(__('Endpoint'))
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('services.'.$this->slug.'.domain', $data['domain']);
        $this->configService->set('services.'.$this->slug.'.secret', $data['secret']);
        $this->configService->set('services.'.$this->slug.'.endpoint', $data['endpoint']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
