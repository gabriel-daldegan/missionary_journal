<?php

namespace App\Livewire\Checkout;

use App\Models\Plan;
use App\Services\PlanService;
use App\Services\SessionService;
use App\Services\TenantCreationService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SubscriptionTenantPicker extends Component
{
    public $tenant;

    private SessionService $sessionService;

    private TenantCreationService $tenantCreationService;

    private PlanService $planService;

    public function boot(SessionService $sessionService, TenantCreationService $tenantCreationService, PlanService $planService)
    {
        $this->sessionService = $sessionService;
        $this->tenantCreationService = $tenantCreationService;
        $this->planService = $planService;
    }

    public function mount()
    {
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $plan = $this->resolveCurrentPlan();

        if (! empty($subscriptionCheckoutDto->tenantUuid)) {
            $this->tenant = $subscriptionCheckoutDto->tenantUuid;
        } else {
            $this->tenant = $this->tenantCreationService->findUserTenantsForNewSubscription(auth()->user(), $plan)->first()?->uuid;
        }

        $subscriptionCheckoutDto->tenantUuid = $this->tenant;
        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);
    }

    public function updatedTenant(string $value)
    {
        $plan = $this->resolveCurrentPlan();

        if (! empty($value)) {

            $tenant = $this->tenantCreationService->findUserTenantForNewSubscriptionByUuid(auth()->user(), $value, $plan);

            if ($tenant === null) {
                throw ValidationException::withMessages([
                    'tenant' => __('This workspace has too many users for the selected plan.'),
                ]);
            }
        }

        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $subscriptionCheckoutDto->shouldCreateNewTenant = empty($value);
        $subscriptionCheckoutDto->tenantUuid = $value;
        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);
    }

    public function render()
    {
        $plan = $this->resolveCurrentPlan();

        return view('livewire.checkout.subscription-tenant-picker', [
            'userTenants' => $this->tenantCreationService->findUserTenantsForNewSubscription(auth()->user(), $plan),
        ]);
    }

    private function resolveCurrentPlan(): ?Plan
    {
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        if (empty($subscriptionCheckoutDto->planSlug)) {
            return null;
        }

        return $this->planService->getActivePlanBySlug($subscriptionCheckoutDto->planSlug);
    }
}
