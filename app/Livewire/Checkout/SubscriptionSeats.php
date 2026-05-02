<?php

namespace App\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Models\Plan;
use App\Services\CalculationService;
use App\Services\PlanService;
use App\Services\SessionService;
use App\Services\SubscriptionService;
use App\Services\TenantSubscriptionService;
use Livewire\Component;

class SubscriptionSeats extends Component
{
    public $planType;

    public $quantity;

    public $planSlug;

    public $maxQuantity;

    public ?int $includedSeats = null;

    public ?int $extraSeatPrice = null;

    public ?string $extraSeatCurrencyCode = null;

    private SessionService $sessionService;

    private PlanService $planService;

    private SubscriptionService $subscriptionService;

    private TenantSubscriptionService $tenantSubscriptionService;

    private CalculationService $calculationService;

    public function boot(
        SessionService $sessionService,
        PlanService $planService,
        SubscriptionService $subscriptionService,
        TenantSubscriptionService $tenantSubscriptionService,
        CalculationService $calculationService,
    ) {
        $this->sessionService = $sessionService;
        $this->planService = $planService;
        $this->subscriptionService = $subscriptionService;
        $this->tenantSubscriptionService = $tenantSubscriptionService;
        $this->calculationService = $calculationService;
    }

    public function mount(Plan $plan)
    {
        $this->planType = $plan->type;
        $this->planSlug = $plan->slug;
        $this->quantity = $this->sessionService->getSubscriptionCheckoutDto()->quantity;
        $this->maxQuantity = $plan->max_users_per_tenant;

        $planPrice = $this->calculationService->getPlanPrice($plan);
        if ($planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value) {
            $this->includedSeats = $planPrice->included_seats;
            $this->extraSeatPrice = $planPrice->extra_seat_price;
            $this->extraSeatCurrencyCode = $planPrice->currency->code;
        }
    }

    public function updatedQuantity(int $value)
    {
        $plan = $this->planService->getActivePlanBySlug($this->planSlug);

        $maxRule = '';
        if ($plan->max_users_per_tenant > 0) {
            $maxRule = '|max:'.$plan->max_users_per_tenant;
        }

        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        $min = 1;

        if ($subscriptionCheckoutDto->subscriptionId !== null) {
            $subscription = $this->subscriptionService->findById($subscriptionCheckoutDto->subscriptionId);
            $min = $this->tenantSubscriptionService->calculateCurrentSubscriptionQuantity($subscription);
        }

        $this->validate([
            'quantity' => 'required|integer|min:'.$min.$maxRule,
        ]);

        $subscriptionCheckoutDto->quantity = $value;
        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->dispatch('calculations-updated')->to(SubscriptionTotals::class);
    }

    public function render()
    {
        return view('livewire.checkout.subscription-seats');
    }
}
