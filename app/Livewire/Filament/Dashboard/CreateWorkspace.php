<?php

namespace App\Livewire\Filament\Dashboard;

use App\Services\TenantCreationService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Livewire\Component;

class CreateWorkspace extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        if (! config('app.allow_user_to_create_tenants_from_dashboard', false)) {
            abort(403);
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Workspace Name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('Acme Inc.')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        /** @var TenantCreationService $tenantCreationService */
        $tenantCreationService = app(TenantCreationService::class);

        $user = auth()->user();

        $tenant = $tenantCreationService->createTenant($user, $data['name']);

        Notification::make()
            ->title(__('Workspace created successfully'))
            ->success()
            ->send();

        $this->redirect(route('filament.dashboard.pages.dashboard', ['tenant' => $tenant->uuid]));
    }

    public function render()
    {
        return view('livewire.filament.dashboard.create-workspace');
    }
}
