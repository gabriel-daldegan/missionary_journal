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

class AmazonSesSettings extends Component implements HasForms
{
    private ConfigService $configService;

    protected string $slug = 'ses';

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
            'key' => $this->configService->get('services.'.$this->slug.'.key'),
            'secret' => $this->configService->get('services.'.$this->slug.'.secret'),
            'region' => $this->configService->get('services.'.$this->slug.'.region'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('key')
                            ->label(__('Key'))
                            ->required(),
                        TextInput::make('secret')
                            ->label(__('Secret'))
                            ->password()
                            ->required(),
                        TextInput::make('region')
                            ->label(__('Region'))
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('services.'.$this->slug.'.key', $data['key']);
        $this->configService->set('services.'.$this->slug.'.secret', $data['secret']);
        $this->configService->set('services.'.$this->slug.'.region', $data['region']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
