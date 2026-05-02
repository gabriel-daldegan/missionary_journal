<?php

namespace App\Livewire;

use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use App\Services\DiscountService;
use App\Services\SubscriptionDiscountService;
use App\Services\SubscriptionService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Livewire\Component;

class AddSubscriptionDiscountForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public string $subscriptionUuid;

    private SubscriptionService $subscriptionService;

    private DiscountService $discountService;

    private SubscriptionDiscountService $subscriptionDiscountService;

    public function boot(
        SubscriptionService $subscriptionService,
        DiscountService $discountService,
        SubscriptionDiscountService $subscriptionDiscountService,
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->discountService = $discountService;
        $this->subscriptionDiscountService = $subscriptionDiscountService;
    }

    public function render()
    {
        return view('livewire.add-subscription-discount-form', [
            'backUrl' => SubscriptionResource::getUrl(),
        ]);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->nullable(false),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();
        $code = $data['code'];
        $user = auth()->user();

        $subscription = $this->subscriptionService->findByUuidOrFail($this->subscriptionUuid);

        $result = $this->subscriptionDiscountService->applyDiscount($subscription, $code, $user);

        if (! $result) {

            Notification::make()
                ->title(__('Could not apply discount code.'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('Discount code has been applied.'))
            ->send();

        $this->redirect(SubscriptionResource::getUrl());
    }
}
