<?php

namespace App\Livewire\Checkout;

use App\Dto\SubscriptionCheckoutDto;
use App\Dto\TotalsDto;
use App\Models\Plan;
use App\Services\CalculationService;
use App\Services\DiscountService;
use App\Services\SessionService;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionTotals extends Component
{
    public $page;

    public $planSlug;

    public $planHasTrial = false;

    public $isTrailSkipped = false;

    public $subtotal;

    public $discountAmount;

    public $amountDue;

    public $currencyCode;

    public $code;

    public ?string $unitMeterName;

    public ?string $planPriceType = null;

    public ?string $pricePerUnit = null;

    public ?array $tiers = null;

    public int $setupFee = 0;

    public ?int $basePrice = null;

    public ?int $includedSeats = null;

    public ?int $extraSeatPrice = null;

    public ?int $extraSeats = null;

    public bool $canAddDiscount = true;

    private DiscountService $discountService;

    private CalculationService $calculationService;

    private SessionService $sessionService;

    public function boot(
        DiscountService $discountService,
        CalculationService $calculationService,
        SessionService $sessionService,
    ) {
        $this->discountService = $discountService;
        $this->calculationService = $calculationService;
        $this->sessionService = $sessionService;
    }

    public function mount(TotalsDto $totals, Plan $plan, $page, bool $canAddDiscount = true, bool $isTrailSkipped = false)
    {
        $this->page = $page;
        $this->planSlug = $plan->slug;
        $this->planHasTrial = $plan->has_trial;
        $this->isTrailSkipped = $isTrailSkipped;
        $this->subtotal = $totals->subtotal;
        $this->setupFee = $totals->setupFee;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
        $this->unitMeterName = $plan->meter?->name;
        $this->planPriceType = $totals->planPriceType;
        $this->pricePerUnit = $totals->pricePerUnit;
        $this->tiers = $totals->tiers;
        $this->basePrice = $totals->basePrice;
        $this->includedSeats = $totals->includedSeats;
        $this->extraSeatPrice = $totals->extraSeatPrice;
        $this->extraSeats = $totals->extraSeats;
        $this->canAddDiscount = $canAddDiscount;

        $this->applyCouponFromSession($plan);
    }

    private function applyCouponFromSession(Plan $plan): void
    {
        $couponCode = $this->sessionService->getCouponCode();

        if ($couponCode === null) {
            return;
        }

        if (! $this->discountService->isCodeRedeemableForPlan($couponCode, auth()->user(), $plan)) {
            return;
        }

        $this->sessionService->clearCouponCode();

        /** @var SubscriptionCheckoutDto $subscriptionCheckoutDto */
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        $subscriptionCheckoutDto->discountCode = $couponCode;
        $subscriptionCheckoutDto->planSlug = $this->planSlug;

        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->updateTotals();
    }

    public function getCodeFromSession(): ?string
    {
        return $this->sessionService->getSubscriptionCheckoutDto()->discountCode;
    }

    public function add()
    {
        $code = $this->code;

        if ($code === null) {
            session()->flash('error', __('Please enter a discount code.'));

            return;
        }

        $plan = Plan::where('slug', $this->planSlug)->where('is_active', true)->firstOrFail();

        $isRedeemable = $this->discountService->isCodeRedeemableForPlan($code, auth()->user(), $plan);

        if (! $isRedeemable) {
            session()->flash('error', __('This discount code is invalid.'));

            return;
        }

        /** @var SubscriptionCheckoutDto $subscriptionCheckoutDto */
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        $subscriptionCheckoutDto->discountCode = $code;
        $subscriptionCheckoutDto->planSlug = $this->planSlug;

        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        $this->updateTotals();

        session()->flash('success', __('The discount code has been applied.'));
    }

    public function remove()
    {
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        $subscriptionCheckoutDto->discountCode = null;

        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        session()->flash('success', __('The discount code has been removed.'));

        $this->updateTotals();
    }

    #[On('calculations-updated')]
    public function updateTotals()
    {
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        $totals = $this->calculationService->calculatePlanTotals(
            auth()->user(),
            $this->planSlug,
            $subscriptionCheckoutDto->discountCode,
            $subscriptionCheckoutDto->quantity,
        );

        $this->subtotal = $totals->subtotal;
        $this->setupFee = $totals->setupFee;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;
        $this->basePrice = $totals->basePrice;
        $this->includedSeats = $totals->includedSeats;
        $this->extraSeatPrice = $totals->extraSeatPrice;
        $this->extraSeats = $totals->extraSeats;
    }

    public function render()
    {
        return view('livewire.checkout.subscription-totals', [
            'addedCode' => $this->getCodeFromSession(),
        ]);
    }
}
