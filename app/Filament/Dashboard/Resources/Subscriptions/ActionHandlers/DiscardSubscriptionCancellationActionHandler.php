<?php

namespace App\Filament\Dashboard\Resources\Subscriptions\ActionHandlers;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use App\Services\PaymentProviders\PaymentService;
use App\Services\SubscriptionService;
use App\Services\TenantPermissionService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class DiscardSubscriptionCancellationActionHandler
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService,
        private TenantPermissionService $tenantPermissionService,
    ) {}

    public function handle(Subscription $record)
    {
        $user = auth()->user();

        $tenant = Filament::getTenant();

        if (! $this->tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS)) {
            Notification::make()
                ->title(__('You do not have permission to cancel subscriptions.'))
                ->danger()
                ->send();

            return redirect()->to(SubscriptionResource::getUrl());
        }

        $subscription = $this->subscriptionService->findActiveByTenantAndSubscriptionUuid($tenant, $record->uuid);

        if (! $subscription) {
            Notification::make()
                ->title(__('Error canceling subscription'))
                ->danger()
                ->send();

            return redirect()->to(SubscriptionResource::getUrl());
        }

        $paymentProvider = $subscription->paymentProvider()->first();

        $paymentProviderStrategy = $this->paymentService->getPaymentProviderBySlug(
            $paymentProvider->slug
        );

        $this->subscriptionService->discardSubscriptionCancellation($subscription, $paymentProviderStrategy);

        Notification::make()
            ->title(__('Subscription cancellation discarded'))
            ->success()
            ->send();

        return redirect()->to(SubscriptionResource::getUrl());
    }
}
