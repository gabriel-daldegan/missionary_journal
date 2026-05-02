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

abstract class OauthProviderSettings extends Component implements HasForms
{
    private ConfigService $configService;

    protected string $slug = '';

    use InteractsWithForms;

    public ?array $data = [];

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function render()
    {
        return view('livewire.filament.'.$this->slug.'-settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'client_id' => $this->configService->get('services.'.$this->slug.'.client_id'),
            'client_secret' => $this->configService->get('services.'.$this->slug.'.client_secret'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('client_id')
                            ->label(__('Client ID'))
                            ->required(),
                        TextInput::make('client_secret')
                            ->label(__('Client Secret'))
                            ->password()
                            ->required(),
                    ])->columnSpan([
                        'sm' => 6,
                        'xl' => 8,
                        '2xl' => 8,
                    ]),
                Section::make()->schema([
                    ViewField::make('how-to')
                        ->label(__('Stripe Settings'))
                        ->view('filament.admin.resources.oauth-login-provider-resource.pages.partials.'.$this->slug.'-how-to'),
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

        $this->configService->set('services.'.$this->slug.'.client_id', $data['client_id']);
        $this->configService->set('services.'.$this->slug.'.client_secret', $data['client_secret']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
