<?php

namespace App\Livewire\Checkout;

use App\Services\SessionService;
use App\Services\TenantCreationService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProductTenantPicker extends Component
{
    public $tenant;

    private SessionService $sessionService;

    private TenantCreationService $tenantCreationService;

    public function boot(SessionService $sessionService, TenantCreationService $tenantCreationService)
    {
        $this->sessionService = $sessionService;
        $this->tenantCreationService = $tenantCreationService;
    }

    public function mount()
    {
        $cartDto = $this->sessionService->getCartDto();

        if (! empty($cartDto->tenantUuid)) {
            $this->tenant = $cartDto->tenantUuid;
        } else {
            $this->tenant = $this->tenantCreationService->findUserTenantsForNewOrder(auth()->user())->first()?->uuid;
        }

        $cartDto->tenantUuid = $this->tenant;
        $this->sessionService->saveCartDto($cartDto);
    }

    public function updatedTenant(string $value)
    {
        if (! empty($value)) {

            $tenant = $this->tenantCreationService->findUserTenantForNewOrderByUuid(auth()->user(), $value);

            if ($tenant === null) {
                throw ValidationException::withMessages([
                    'tenant' => __('You do not have access to this account.'),
                ]);
            }
        }

        $cartDto = $this->sessionService->getCartDto();
        $cartDto->shouldCreateNewTenant = empty($value);
        $cartDto->tenantUuid = $value;
        $this->sessionService->saveCartDto($cartDto);
    }

    public function render()
    {
        return view('livewire.checkout.product-tenant-picker', [
            'userTenants' => $this->tenantCreationService->findUserTenantsForNewOrder(auth()->user()),
        ]);
    }
}
